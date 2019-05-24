<?php

namespace Drupal\migrate;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Utility\Error;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a migrate executable class.
 */
class MigrateExecutable implements MigrateExecutableInterface {
  use StringTranslationTrait;

  /**
   * The configuration of the migration to do.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Status of one row.
   *
   * The value is a MigrateIdMapInterface::STATUS_* constant, for example:
   * STATUS_IMPORTED.
   *
   * @var int
   */
  protected $sourceRowStatus;

  /**
   * The ratio of the memory limit at which an operation will be interrupted.
   *
   * @var float
   */
  protected $memoryThreshold = 0.85;

  /**
   * The PHP memory_limit expressed in bytes.
   *
   * @var int
   */
  protected $memoryLimit;

  /**
   * The configuration values of the source.
   *
   * @var array
   */
  protected $sourceIdValues;

  /**
   * An array of counts. Initially used for cache hit/miss tracking.
   *
   * @var array
   */
  protected $counts = [];

  /**
   * The source.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourceInterface
   */
  protected $source;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Migration message service.
   *
   * @todo https://www.drupal.org/node/2822663 Make this protected.
   *
   * @var \Drupal\migrate\MigrateMessageInterface
   */
  public $message;

  /**
   * Constructs a MigrateExecutable and verifies and sets the memory limit.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to run.
   * @param \Drupal\migrate\MigrateMessageInterface $message
   *   (optional) The migrate message service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   (optional) The event dispatcher.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message = NULL, EventDispatcherInterface $event_dispatcher = NULL) {
    $this->migration = $migration;
    $this->message = $message ?: new MigrateMessage();
    $this->getIdMap()->setMessage($this->message);
    $this->eventDispatcher = $event_dispatcher;
    // Record the memory limit in bytes
    $limit = trim(ini_get('memory_limit'));
    if ($limit == '-1') {
      $this->memoryLimit = PHP_INT_MAX;
    }
    else {
      $this->memoryLimit = Bytes::toInt($limit);
    }
  }

  /**
   * Returns the source.
   *
   * Makes sure source is initialized based on migration settings.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface
   *   The source.
   */
  protected function getSource() {
    if (!isset($this->source)) {
      $this->source = $this->migration->getSourcePlugin();
    }
    return $this->source;
  }

  /**
   * Gets the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected function getEventDispatcher() {
    if (!$this->eventDispatcher) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function import() {
    // Only begin the import operation if the migration is currently idle.
    if ($this->migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      $this->message->display($this->t('Migration @id is busy with another operation: @status',
        [
          '@id' => $this->migration->id(),
          '@status' => $this->t($this->migration->getStatusLabel()),
        ]), 'error');
      return MigrationInterface::RESULT_FAILED;
    }
    $this->getEventDispatcher()->dispatch(MigrateEvents::PRE_IMPORT, new MigrateImportEvent($this->migration, $this->message));

    // Knock off migration if the requirements haven't been met.
    try {
      $this->migration->checkRequirements();
    }
    catch (RequirementsException $e) {
      $this->message->display(
        $this->t(
          'Migration @id did not meet the requirements. @message @requirements',
          [
            '@id' => $this->migration->id(),
            '@message' => $e->getMessage(),
            '@requirements' => $e->getRequirementsString(),
          ]
        ),
        'error'
      );

      return MigrationInterface::RESULT_FAILED;
    }

    $this->migration->setStatus(MigrationInterface::STATUS_IMPORTING);
    $return = MigrationInterface::RESULT_COMPLETED;
    $source = $this->getSource();
    $id_map = $this->getIdMap();

    try {
      $source->rewind();
    }
    catch (\Exception $e) {
      $this->message->display(
        $this->t('Migration failed with source plugin exception: @e', ['@e' => $e->getMessage()]), 'error');
      $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
      return MigrationInterface::RESULT_FAILED;
    }

    $destination = $this->migration->getDestinationPlugin();
    while ($source->valid()) {
      $row = $source->current();
      $this->sourceIdValues = $row->getSourceIdValues();

      try {
        $this->processRow($row);
        $save = TRUE;
      }
      catch (MigrateException $e) {
        $this->getIdMap()->saveIdMapping($row, [], $e->getStatus());
        $this->saveMessage($e->getMessage(), $e->getLevel());
        $save = FALSE;
      }
      catch (MigrateSkipRowException $e) {
        if ($e->getSaveToMap()) {
          $id_map->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_IGNORED);
        }
        if ($message = trim($e->getMessage())) {
          $this->saveMessage($message, MigrationInterface::MESSAGE_INFORMATIONAL);
        }
        $save = FALSE;
      }

      if ($save) {
        try {
          $this->getEventDispatcher()->dispatch(MigrateEvents::PRE_ROW_SAVE, new MigratePreRowSaveEvent($this->migration, $this->message, $row));
          $destination_ids = $id_map->lookupDestinationIds($this->sourceIdValues);
          $destination_id_values = $destination_ids ? reset($destination_ids) : [];
          $destination_id_values = $destination->import($row, $destination_id_values);
          $this->getEventDispatcher()->dispatch(MigrateEvents::POST_ROW_SAVE, new MigratePostRowSaveEvent($this->migration, $this->message, $row, $destination_id_values));
          if ($destination_id_values) {
            // We do not save an idMap entry for config.
            if ($destination_id_values !== TRUE) {
              $id_map->saveIdMapping($row, $destination_id_values, $this->sourceRowStatus, $destination->rollbackAction());
            }
          }
          else {
            $id_map->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_FAILED);
            if (!$id_map->messageCount()) {
              $message = $this->t('New object was not saved, no error provided');
              $this->saveMessage($message);
              $this->message->display($message);
            }
          }
        }
        catch (MigrateException $e) {
          $this->getIdMap()->saveIdMapping($row, [], $e->getStatus());
          $this->saveMessage($e->getMessage(), $e->getLevel());
        }
        catch (\Exception $e) {
          $this->getIdMap()->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_FAILED);
          $this->handleException($e);
        }
      }

      $this->sourceRowStatus = MigrateIdMapInterface::STATUS_IMPORTED;

      // Check for memory exhaustion.
      if (($return = $this->checkStatus()) != MigrationInterface::RESULT_COMPLETED) {
        break;
      }

      // If anyone has requested we stop, return the requested result.
      if ($this->migration->getStatus() == MigrationInterface::STATUS_STOPPING) {
        $return = $this->migration->getInterruptionResult();
        $this->migration->clearInterruptionResult();
        break;
      }

      try {
        $source->next();
      }
      catch (\Exception $e) {
        $this->message->display(
          $this->t('Migration failed with source plugin exception: @e',
            ['@e' => $e->getMessage()]), 'error');
        $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
        return MigrationInterface::RESULT_FAILED;
      }
    }

    $this->getEventDispatcher()->dispatch(MigrateEvents::POST_IMPORT, new MigrateImportEvent($this->migration, $this->message));
    $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback() {
    // Only begin the rollback operation if the migration is currently idle.
    if ($this->migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      $this->message->display($this->t('Migration @id is busy with another operation: @status', ['@id' => $this->migration->id(), '@status' => $this->t($this->migration->getStatusLabel())]), 'error');
      return MigrationInterface::RESULT_FAILED;
    }

    // Announce that rollback is about to happen.
    $this->getEventDispatcher()->dispatch(MigrateEvents::PRE_ROLLBACK, new MigrateRollbackEvent($this->migration));

    // Optimistically assume things are going to work out; if not, $return will be
    // updated to some other status.
    $return = MigrationInterface::RESULT_COMPLETED;

    $this->migration->setStatus(MigrationInterface::STATUS_ROLLING_BACK);
    $id_map = $this->getIdMap();
    $destination = $this->migration->getDestinationPlugin();

    // Loop through each row in the map, and try to roll it back.
    $id_map->rewind();
    while ($id_map->valid()) {
      $destination_key = $id_map->currentDestination();
      if ($destination_key) {
        $map_row = $id_map->getRowByDestination($destination_key);
        if ($map_row['rollback_action'] == MigrateIdMapInterface::ROLLBACK_DELETE) {
          $this->getEventDispatcher()
            ->dispatch(MigrateEvents::PRE_ROW_DELETE, new MigrateRowDeleteEvent($this->migration, $destination_key));
          $destination->rollback($destination_key);
          $this->getEventDispatcher()
            ->dispatch(MigrateEvents::POST_ROW_DELETE, new MigrateRowDeleteEvent($this->migration, $destination_key));
        }
        // We're now done with this row, so remove it from the map.
        $id_map->deleteDestination($destination_key);
      }
      else {
        // If there is no destination key the import probably failed and we can
        // remove the row without further action.
        $source_key = $id_map->currentSource();
        $id_map->delete($source_key);
      }
      $id_map->next();

      // Check for memory exhaustion.
      if (($return = $this->checkStatus()) != MigrationInterface::RESULT_COMPLETED) {
        break;
      }

      // If anyone has requested we stop, return the requested result.
      if ($this->migration->getStatus() == MigrationInterface::STATUS_STOPPING) {
        $return = $this->migration->getInterruptionResult();
        $this->migration->clearInterruptionResult();
        break;
      }
    }

    // Notify modules that rollback attempt was complete.
    $this->getEventDispatcher()->dispatch(MigrateEvents::POST_ROLLBACK, new MigrateRollbackEvent($this->migration));
    $this->migration->setStatus(MigrationInterface::STATUS_IDLE);

    return $return;
  }

  /**
   * Get the ID map from the current migration.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface
   *   The ID map.
   */
  protected function getIdMap() {
    return $this->migration->getIdMap();
  }

  /**
   * {@inheritdoc}
   */
  public function processRow(Row $row, array $process = NULL, $value = NULL) {
    foreach ($this->migration->getProcessPlugins($process) as $destination => $plugins) {
      $multiple = FALSE;
      /** @var $plugin \Drupal\migrate\Plugin\MigrateProcessInterface */
      foreach ($plugins as $plugin) {
        $definition = $plugin->getPluginDefinition();
        // Many plugins expect a scalar value but the current value of the
        // pipeline might be multiple scalars (this is set by the previous
        // plugin) and in this case the current value needs to be iterated
        // and each scalar separately transformed.
        if ($multiple && !$definition['handle_multiples']) {
          $new_value = [];
          if (!is_array($value)) {
            throw new MigrateException(sprintf('Pipeline failed at %s plugin for destination %s: %s received instead of an array,', $plugin->getPluginId(), $destination, $value));
          }
          $break = FALSE;
          foreach ($value as $scalar_value) {
            try {
              $new_value[] = $plugin->transform($scalar_value, $this, $row, $destination);
            }
            catch (MigrateSkipProcessException $e) {
              $new_value[] = NULL;
              $break = TRUE;
            }
          }
          $value = $new_value;
          if ($break) {
            break;
          }
        }
        else {
          try {
            $value = $plugin->transform($value, $this, $row, $destination);
          }
          catch (MigrateSkipProcessException $e) {
            $value = NULL;
            break;
          }
          $multiple = $plugin->multiple();
        }
      }
      // Ensure all values, including nulls, are migrated.
      if ($plugins) {
        if (isset($value)) {
          $row->setDestinationProperty($destination, $value);
        }
        else {
          $row->setEmptyDestinationProperty($destination);
        }
      }
      // Reset the value.
      $value = NULL;
    }
  }

  /**
   * Fetches the key array for the current source record.
   *
   * @return array
   *   The current source IDs.
   */
  protected function currentSourceIds() {
    return $this->getSource()->getCurrentIds();
  }

  /**
   * {@inheritdoc}
   */
  public function saveMessage($message, $level = MigrationInterface::MESSAGE_ERROR) {
    $this->getIdMap()->saveMessage($this->sourceIdValues, $message, $level);
  }

  /**
   * Takes an Exception object and both saves and displays it.
   *
   * Pulls in additional information on the location triggering the exception.
   *
   * @param \Exception $exception
   *   Object representing the exception.
   * @param bool $save
   *   (optional) Whether to save the message in the migration's mapping table.
   *   Set to FALSE in contexts where this doesn't make sense.
   */
  protected function handleException(\Exception $exception, $save = TRUE) {
    $result = Error::decodeException($exception);
    $message = $result['@message'] . ' (' . $result['%file'] . ':' . $result['%line'] . ')';
    if ($save) {
      $this->saveMessage($message);
    }
    $this->message->display($message, 'error');
  }

  /**
   * Checks for exceptional conditions, and display feedback.
   */
  protected function checkStatus() {
    if ($this->memoryExceeded()) {
      return MigrationInterface::RESULT_INCOMPLETE;
    }
    return MigrationInterface::RESULT_COMPLETED;
  }

  /**
   * Tests whether we've exceeded the desired memory threshold.
   *
   * If so, output a message.
   *
   * @return bool
   *   TRUE if the threshold is exceeded, otherwise FALSE.
   */
  protected function memoryExceeded() {
    $usage = $this->getMemoryUsage();
    $pct_memory = $usage / $this->memoryLimit;
    if (!$threshold = $this->memoryThreshold) {
      return FALSE;
    }
    if ($pct_memory > $threshold) {
      $this->message->display(
        $this->t(
          'Memory usage is @usage (@pct% of limit @limit), reclaiming memory.',
          [
            '@pct' => round($pct_memory * 100),
            '@usage' => $this->formatSize($usage),
            '@limit' => $this->formatSize($this->memoryLimit),
          ]
        ),
        'warning'
      );
      $usage = $this->attemptMemoryReclaim();
      $pct_memory = $usage / $this->memoryLimit;
      // Use a lower threshold - we don't want to be in a situation where we keep
      // coming back here and trimming a tiny amount
      if ($pct_memory > (0.90 * $threshold)) {
        $this->message->display(
          $this->t(
            'Memory usage is now @usage (@pct% of limit @limit), not enough reclaimed, starting new batch',
            [
              '@pct' => round($pct_memory * 100),
              '@usage' => $this->formatSize($usage),
              '@limit' => $this->formatSize($this->memoryLimit),
            ]
          ),
          'warning'
        );
        return TRUE;
      }
      else {
        $this->message->display(
          $this->t(
            'Memory usage is now @usage (@pct% of limit @limit), reclaimed enough, continuing',
            [
              '@pct' => round($pct_memory * 100),
              '@usage' => $this->formatSize($usage),
              '@limit' => $this->formatSize($this->memoryLimit),
            ]
          ),
          'warning');
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns the memory usage so far.
   *
   * @return int
   *   The memory usage.
   */
  protected function getMemoryUsage() {
    return memory_get_usage();
  }

  /**
   * Tries to reclaim memory.
   *
   * @return int
   *   The memory usage after reclaim.
   */
  protected function attemptMemoryReclaim() {
    // First, try resetting Drupal's static storage - this frequently releases
    // plenty of memory to continue.
    drupal_static_reset();

    // Entity storage can blow up with caches so clear them out.
    $entity_type_manager = \Drupal::entityTypeManager();
    foreach ($entity_type_manager->getDefinitions() as $id => $definition) {
      $entity_type_manager->getStorage($id)->resetCache();
    }

    // @TODO: explore resetting the container.

    // Run garbage collector to further reduce memory.
    gc_collect_cycles();

    return memory_get_usage();
  }

  /**
   * Generates a string representation for the given byte count.
   *
   * @param int $size
   *   A size in bytes.
   *
   * @return string
   *   A translated string representation of the size.
   */
  protected function formatSize($size) {
    return format_size($size);
  }

}

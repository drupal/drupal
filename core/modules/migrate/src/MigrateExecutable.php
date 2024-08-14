<?php

namespace Drupal\migrate;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Migration message service.
   *
   * @var \Drupal\migrate\MigrateMessageInterface
   *
   * @todo https://www.drupal.org/node/2822663 Make this protected.
   */
  public $message;

  /**
   * Constructs a MigrateExecutable and verifies and sets the memory limit.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to run.
   * @param \Drupal\migrate\MigrateMessageInterface $message
   *   (optional) The migrate message service.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   (optional) The event dispatcher.
   */
  public function __construct(MigrationInterface $migration, ?MigrateMessageInterface $message = NULL, ?EventDispatcherInterface $event_dispatcher = NULL) {
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
      $this->memoryLimit = Bytes::toNumber($limit);
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
   * @return \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
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
    $this->getEventDispatcher()->dispatch(new MigrateImportEvent($this->migration, $this->message), MigrateEvents::PRE_IMPORT);

    // Knock off migration if the requirements haven't been met.
    try {
      $this->migration->checkRequirements();
    }
    catch (RequirementsException $e) {
      $this->message->display(
        $this->t(
          'Migration @id did not meet the requirements. @message',
          [
            '@id' => $this->migration->id(),
            '@message' => $e->getMessage(),
          ]
        ),
        'error'
      );

      return MigrationInterface::RESULT_FAILED;
    }

    $this->migration->setStatus(MigrationInterface::STATUS_IMPORTING);
    $source = $this->getSource();

    try {
      $source->rewind();
    }
    catch (\Exception $e) {
      $this->message->display(
        $this->t('Migration failed with source plugin exception: @e in @file line @line', [
          '@e' => $e->getMessage(),
          '@file' => $e->getFile(),
          '@line' => $e->getLine(),
        ]), 'error');
      $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
      return MigrationInterface::RESULT_FAILED;
    }

    // Get the process pipeline.
    $pipeline = FALSE;
    if ($source->valid()) {
      try {
        $pipeline = $this->migration->getProcessPlugins();
      }
      catch (MigrateException $e) {
        $row = $source->current();
        $this->sourceIdValues = $row->getSourceIdValues();
        $this->getIdMap()->saveIdMapping($row, [], $e->getStatus());
        $this->saveMessage($e->getMessage(), $e->getLevel());
      }
    }

    $return = MigrationInterface::RESULT_COMPLETED;
    if ($pipeline) {
      $id_map = $this->getIdMap();
      $destination = $this->migration->getDestinationPlugin();
      while ($source->valid()) {
        $row = $source->current();
        $this->sourceIdValues = $row->getSourceIdValues();

        try {
          foreach ($pipeline as $destination_property_name => $plugins) {
            $this->processPipeline($row, $destination_property_name, $plugins, NULL);
          }
          $save = TRUE;
        }
        catch (MigrateException $e) {
          $this->getIdMap()->saveIdMapping($row, [], $e->getStatus());
          $msg = sprintf("%s:%s:%s", $this->migration->getPluginId(), $destination_property_name, $e->getMessage());
          $this->saveMessage($msg, $e->getLevel());
          $save = FALSE;
        }
        catch (MigrateSkipRowException $e) {
          if ($e->getSaveToMap()) {
            $id_map->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_IGNORED);
          }
          if ($message = trim($e->getMessage())) {
            $msg = sprintf("%s:%s: %s", $this->migration->getPluginId(), $destination_property_name, $message);
            $this->saveMessage($msg, MigrationInterface::MESSAGE_INFORMATIONAL);
          }
          $save = FALSE;
        }

        if ($save) {
          try {
            $this->getEventDispatcher()
              ->dispatch(new MigratePreRowSaveEvent($this->migration, $this->message, $row), MigrateEvents::PRE_ROW_SAVE);
            $destination_ids = $id_map->lookupDestinationIds($this->sourceIdValues);
            $destination_id_values = $destination_ids ? reset($destination_ids) : [];
            $destination_id_values = $destination->import($row, $destination_id_values);
            $this->getEventDispatcher()
              ->dispatch(new MigratePostRowSaveEvent($this->migration, $this->message, $row, $destination_id_values), MigrateEvents::POST_ROW_SAVE);
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
            $this->getIdMap()
              ->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_FAILED);
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
            $this->t('Migration failed with source plugin exception: @e in @file line @line', [
              '@e' => $e->getMessage(),
              '@file' => $e->getFile(),
              '@line' => $e->getLine(),
            ]), 'error');
          $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
          return MigrationInterface::RESULT_FAILED;
        }
      }
    }

    $this->getEventDispatcher()->dispatch(new MigrateImportEvent($this->migration, $this->message), MigrateEvents::POST_IMPORT);
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
    $this->getEventDispatcher()->dispatch(new MigrateRollbackEvent($this->migration), MigrateEvents::PRE_ROLLBACK);

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
        if (!isset($map_row['rollback_action']) || $map_row['rollback_action'] == MigrateIdMapInterface::ROLLBACK_DELETE) {
          $this->getEventDispatcher()
            ->dispatch(new MigrateRowDeleteEvent($this->migration, $destination_key), MigrateEvents::PRE_ROW_DELETE);
          $destination->rollback($destination_key);
          $this->getEventDispatcher()
            ->dispatch(new MigrateRowDeleteEvent($this->migration, $destination_key), MigrateEvents::POST_ROW_DELETE);
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
    $this->getEventDispatcher()->dispatch(new MigrateRollbackEvent($this->migration), MigrateEvents::POST_ROLLBACK);
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
  public function processRow(Row $row, ?array $process = NULL, $value = NULL) {
    foreach ($this->migration->getProcessPlugins($process) as $destination => $plugins) {
      $this->processPipeline($row, $destination, $plugins, $value);
    }
  }

  /**
   * Runs a process pipeline.
   *
   * @param \Drupal\migrate\Row $row
   *   The $row to be processed.
   * @param string $destination
   *   The destination property name.
   * @param array $plugins
   *   The process pipeline plugins.
   * @param mixed $value
   *   (optional) Initial value of the pipeline for the destination.
   *
   * @see \Drupal\migrate\MigrateExecutableInterface::processRow
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function processPipeline(Row $row, string $destination, array $plugins, $value) {
    $multiple = FALSE;
    /** @var \Drupal\migrate\Plugin\MigrateProcessInterface $plugin */
    foreach ($plugins as $plugin) {
      $definition = $plugin->getPluginDefinition();
      // Many plugins expect a scalar value but the current value of the
      // pipeline might be multiple scalars (this is set by the previous plugin)
      // and in this case the current value needs to be iterated and each scalar
      // separately transformed.
      if ($multiple && !$definition['handle_multiples']) {
        $new_value = [];
        if (!is_array($value)) {
          throw new MigrateException(sprintf('Pipeline failed at %s plugin for destination %s: %s received instead of an array,', $plugin->getPluginId(), $destination, $value));
        }
        $break = FALSE;
        foreach ($value as $scalar_value) {
          $plugin->reset();
          try {
            $new_value[] = $plugin->transform($scalar_value, $this, $row, $destination);
          }
          catch (MigrateSkipProcessException $e) {
            $new_value[] = NULL;
            $break = TRUE;
          }
          catch (MigrateException $e) {
            // Prepend the process plugin id to the message.
            $message = sprintf("%s: %s", $plugin->getPluginId(), $e->getMessage());
            throw new MigrateException($message);
          }
          if ($plugin->isPipelineStopped()) {
            $break = TRUE;
          }
        }
        $value = $new_value;
        if ($break) {
          break;
        }
      }
      else {
        $plugin->reset();
        try {
          $value = $plugin->transform($value, $this, $row, $destination);
        }
        catch (MigrateSkipProcessException) {
          $value = NULL;
          break;
        }
        catch (MigrateException $e) {
          // Prepend the process plugin id to the message.
          $message = sprintf("%s: %s", $plugin->getPluginId(), $e->getMessage());
          throw new MigrateException($message);
        }
        if ($plugin->isPipelineStopped()) {
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
            '@usage' => ByteSizeMarkup::create($usage, NULL, $this->stringTranslation),
            '@limit' => ByteSizeMarkup::create($this->memoryLimit, NULL, $this->stringTranslation),
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
              '@usage' => ByteSizeMarkup::create($usage, NULL, $this->stringTranslation),
              '@limit' => ByteSizeMarkup::create($this->memoryLimit, NULL, $this->stringTranslation),
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
              '@usage' => ByteSizeMarkup::create($usage, NULL, $this->stringTranslation),
              '@limit' => ByteSizeMarkup::create($this->memoryLimit, NULL, $this->stringTranslation),
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

    // Entity storage can blow up with caches, so clear it out.
    \Drupal::service('entity.memory_cache')->deleteAll();

    // @todo Explore resetting the container.

    // Run garbage collector to further reduce memory.
    gc_collect_cycles();

    return memory_get_usage();
  }

}

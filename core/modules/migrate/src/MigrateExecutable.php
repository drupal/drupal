<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateExecutable.
 */

namespace Drupal\migrate;

use Drupal\Core\Utility\Error;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a migrate executable class.
 */
class MigrateExecutable implements MigrateExecutableInterface {
  use StringTranslationTrait;

  /**
   * The configuration of the migration to do.
   *
   * @var \Drupal\migrate\Entity\Migration
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
   * The rollback action to be saved for the current row.
   *
   * @var int
   */
  public $rollbackAction;

  /**
   * An array of counts. Initially used for cache hit/miss tracking.
   *
   * @var array
   */
  protected $counts = array();

  /**
   * The maximum number of items to pass in a single call during a rollback.
   *
   * For use in bulkRollback(). Can be overridden in derived class constructor.
   *
   * @var int
   */
  protected $rollbackBatchSize = 50;

  /**
   * The object currently being constructed.
   *
   * @var \stdClass
   */
  protected $destinationValues;

  /**
   * The source.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourceInterface
   */
  protected $source;

  /**
   * The current data row retrieved from the source.
   *
   * @var \stdClass
   */
  protected $sourceValues;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a MigrateExecutable and verifies and sets the memory limit.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration to run.
   * @param \Drupal\migrate\MigrateMessageInterface $message
   *   The message to record.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message, EventDispatcherInterface $event_dispatcher = NULL) {
    $this->migration = $migration;
    $this->message = $message;
    $this->migration->getIdMap()->setMessage($message);
    $this->eventDispatcher = $event_dispatcher;
    // Record the memory limit in bytes
    $limit = trim(ini_get('memory_limit'));
    if ($limit == '-1') {
      $this->memoryLimit = PHP_INT_MAX;
    }
    else {
      if (!is_numeric($limit)) {
        $last = strtolower(substr($limit, -1));
        switch ($last) {
          case 'g':
            $limit *= 1024;
          case 'm':
            $limit *= 1024;
          case 'k':
            $limit *= 1024;
            break;
          default:
            $limit = PHP_INT_MAX;
            $this->message->display($this->t('Invalid PHP memory_limit !limit, setting to unlimited.',
              array('!limit' => $limit)));
        }
      }
      $this->memoryLimit = $limit;
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
        array(
          '@id' => $this->migration->id(),
          '@status' => $this->t($this->migration->getStatusLabel()),
        )), 'error');
      return MigrationInterface::RESULT_FAILED;
    }
    $this->getEventDispatcher()->dispatch(MigrateEvents::PRE_IMPORT, new MigrateImportEvent($this->migration));

    // Knock off migration if the requirements haven't been met.
    try {
      $this->migration->checkRequirements();
    }
    catch (RequirementsException $e) {
      $this->message->display(
        $this->t('Migration @id did not meet the requirements. @message @requirements', array(
          '@id' => $this->migration->id(),
          '@message' => $e->getMessage(),
          '@requirements' => $e->getRequirementsString(),
        )), 'error');
      return MigrationInterface::RESULT_FAILED;
    }

    $this->migration->setStatus(MigrationInterface::STATUS_IMPORTING);
    $return = MigrationInterface::RESULT_COMPLETED;
    $source = $this->getSource();
    $id_map = $this->migration->getIdMap();

    try {
      $source->rewind();
    }
    catch (\Exception $e) {
      $this->message->display(
        $this->t('Migration failed with source plugin exception: !e', array('!e' => $e->getMessage())), 'error');
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
        $this->migration->getIdMap()->saveIdMapping($row, array(), $e->getStatus(), $this->rollbackAction);
        $this->saveMessage($e->getMessage(), $e->getLevel());
        $save = FALSE;
      }
      catch (MigrateSkipRowException $e) {
        $id_map->saveIdMapping($row, array(), MigrateIdMapInterface::STATUS_IGNORED, $this->rollbackAction);
        $save = FALSE;
      }

      if ($save) {
        try {
          $this->getEventDispatcher()->dispatch(MigrateEvents::PRE_ROW_SAVE, new MigratePreRowSaveEvent($this->migration, $row));
          $destination_id_values = $destination->import($row, $id_map->lookupDestinationId($this->sourceIdValues));
          $this->getEventDispatcher()->dispatch(MigrateEvents::POST_ROW_SAVE, new MigratePostRowSaveEvent($this->migration, $row, $destination_id_values));
          if ($destination_id_values) {
            // We do not save an idMap entry for config.
            if ($destination_id_values !== TRUE) {
              $id_map->saveIdMapping($row, $destination_id_values, $this->sourceRowStatus, $this->rollbackAction);
            }
          }
          else {
            $id_map->saveIdMapping($row, array(), MigrateIdMapInterface::STATUS_FAILED, $this->rollbackAction);
            if (!$id_map->messageCount()) {
              $message = $this->t('New object was not saved, no error provided');
              $this->saveMessage($message);
              $this->message->display($message);
            }
          }
        }
        catch (MigrateException $e) {
          $this->migration->getIdMap()->saveIdMapping($row, array(), $e->getStatus(), $this->rollbackAction);
          $this->saveMessage($e->getMessage(), $e->getLevel());
        }
        catch (\Exception $e) {
          $this->migration->getIdMap()->saveIdMapping($row, array(), MigrateIdMapInterface::STATUS_FAILED, $this->rollbackAction);
          $this->handleException($e);
        }
      }
      if ($high_water_property = $this->migration->get('highWaterProperty')) {
        $this->migration->saveHighWater($row->getSourceProperty($high_water_property['name']));
      }

      // Reset row properties.
      unset($sourceValues, $destinationValues);
      $this->sourceRowStatus = MigrateIdMapInterface::STATUS_IMPORTED;

      // Check for memory exhaustion.
      if (($return = $this->checkStatus()) != MigrationInterface::RESULT_COMPLETED) {
        break;
      }

      // If anyone has requested we stop, return the requested result.
      if ($this->migration->getStatus() == MigrationInterface::STATUS_STOPPING) {
        $return = $this->migration->getMigrationResult();
        break;
      }

      try {
        $source->next();
      }
      catch (\Exception $e) {
        $this->message->display(
          $this->t('Migration failed with source plugin exception: !e',
            array('!e' => $e->getMessage())), 'error');
        $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
        return MigrationInterface::RESULT_FAILED;
      }
    }

    $this->migration->setMigrationResult($return);
    $this->getEventDispatcher()->dispatch(MigrateEvents::POST_IMPORT, new MigrateImportEvent($this->migration));
    $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
    return $return;
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
          $new_value = array();
          if (!is_array($value)) {
            throw new MigrateException(sprintf('Pipeline failed for destination %s: %s got instead of an array,', $destination, $value));
          }
          $break = FALSE;
          foreach ($value as $scalar_value) {
            try {
              $new_value[] = $plugin->transform($scalar_value, $this, $row, $destination);
            }
            catch (MigrateSkipProcessException $e) {
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
            break;
          }
          $multiple = $multiple || $plugin->multiple();
        }
      }
      // No plugins means do not set.
      if ($plugins) {
        $row->setDestinationProperty($destination, $value);
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
    $this->migration->getIdMap()->saveMessage($this->sourceIdValues, $message, $level);
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
        $this->t('Memory usage is @usage (@pct% of limit @limit), reclaiming memory.',
          array('@pct' => round($pct_memory*100),
                '@usage' => $this->formatSize($usage),
                '@limit' => $this->formatSize($this->memoryLimit))),
        'warning');
      $usage = $this->attemptMemoryReclaim();
      $pct_memory = $usage / $this->memoryLimit;
      // Use a lower threshold - we don't want to be in a situation where we keep
      // coming back here and trimming a tiny amount
      if ($pct_memory > (0.90 * $threshold)) {
        $this->message->display(
          $this->t('Memory usage is now @usage (@pct% of limit @limit), not enough reclaimed, starting new batch',
            array('@pct' => round($pct_memory*100),
                  '@usage' => $this->formatSize($usage),
                  '@limit' => $this->formatSize($this->memoryLimit))),
          'warning');
        return TRUE;
      }
      else {
        $this->message->display(
          $this->t('Memory usage is now @usage (@pct% of limit @limit), reclaimed enough, continuing',
            array('@pct' => round($pct_memory*100),
                  '@usage' => $this->formatSize($usage),
                  '@limit' => $this->formatSize($this->memoryLimit))),
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
    $manager =  \Drupal::entityManager();
    foreach ($manager->getDefinitions() as $id => $definition) {
      $manager->getStorage($id)->resetCache();
    }

    // @TODO: explore resetting the container.

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

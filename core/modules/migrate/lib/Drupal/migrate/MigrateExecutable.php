<?php

/**
 * @file
 * Contains \Drupal\migrate\MigrateExecutable.
 */

namespace Drupal\migrate;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Defines a migrate executable class.
 */
class MigrateExecutable {

  /**
   * The configuration of the migration to do.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface
   */
  protected $migration;

  /**
   * The number of successfully imported rows since feedback was given.
   *
   * @var int
   */
  protected $successes_since_feedback;

  /**
   * The number of rows that were successfully processed.
   *
   * @var int
   */
  protected $total_successes;

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
   * The number of rows processed.
   *
   * The total attempted, whether or not they were successful.
   *
   * @var int
   */
  protected $total_processed;

  /**
   * The queued messages not yet saved.
   *
   * Each element in the array is an array with two keys:
   * - 'message': The message string.
   * - 'level': The level, a MigrationInterface::MESSAGE_* constant.
   *
   * @var array
   */
  protected $queuedMessages = array();

  /**
   * The options that can be set when executing the migration.
   *
   * Values can be set for:
   * - 'limit': Sets a time limit.
   *
   * @var array
   */
  protected $options;

  /**
   * The fraction of the memory limit at which an operation will be interrupted.
   *
   * Can be overridden by a Migration subclass if one would like to push the
   * envelope. Defaults to 85%.
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
   * The fraction of the time limit at which an operation will be interrupted.
   *
   * Can be overridden by a Migration subclass if one would like to push the
   * envelope. Defaults to 90%.
   *
   * @var float
   */
  protected $timeThreshold = 0.90;

  /**
   * The PHP max_execution_time.
   *
   * @var int
   */
  protected $timeLimit;

  /**
   * The configuration values of the source.
   *
   * @var array
   */
  protected $sourceIdValues;

  /**
   * The number of rows processed since feedback was given.
   *
   * @var int
   */
  protected $processed_since_feedback = 0;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Constructs a MigrateExecutable and verifies and sets the memory limit.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration to run.
   * @param \Drupal\migrate\MigrateMessageInterface $message
   *   The message to record.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message) {
    $this->migration = $migration;
    $this->message = $message;
    $this->migration->getIdMap()->setMessage($message);
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
            throw new MigrateException($this->t('Invalid PHP memory_limit !limit',
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
   * @return \Drupal\migrate\Source
   *   The source.
   */
  public function getSource() {
    if (!isset($this->source)) {
      $this->source = new Source($this->migration, $this);
    }
    return $this->source;
  }

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
   * When performing a bulkRollback(), the maximum number of items to pass in
   * a single call. Can be overridden in derived class constructor.
   *
   * @var int
   */
  protected $rollbackBatchSize = 50;

  /**
   * The object currently being constructed
   * @var \stdClass
   */
  protected $destinationValues;

  /**
   * The current data row retrieved from the source.
   * @var \stdClass
   */
  protected $sourceValues;

  /**
   * Perform an import operation - migrate items from source to destination.
   */
  public function import() {
    $return = MigrationInterface::RESULT_COMPLETED;
    $source = $this->getSource();
    $destination = $this->migration->getDestinationPlugin();
    $id_map = $this->migration->getIdMap();

    try {
      $source->rewind();
    }
    catch (\Exception $e) {
      $this->message->display(
        $this->t('Migration failed with source plugin exception: !e',
          array('!e' => $e->getMessage())));
      return MigrationInterface::RESULT_FAILED;
    }
    while ($source->valid()) {
      $row = $source->current();
      if ($this->sourceIdValues = $row->getSourceIdValues()) {
        // Wipe old messages, and save any new messages.
        $id_map->delete($row->getSourceIdValues(), TRUE);
        $this->saveQueuedMessages();
      }

      $this->processRow($row);

      try {
        $destination_id_values = $destination->import($row);
        // @TODO handle the successful but no ID case like config.
        if ($destination_id_values) {
          $id_map->saveIdMapping($row, $destination_id_values, $this->sourceRowStatus, $this->rollbackAction);
          $this->successes_since_feedback++;
          $this->total_successes++;
        }
        else {
          $id_map->saveIdMapping($row, array(), MigrateIdMapInterface::STATUS_FAILED, $this->rollbackAction);
          if ($id_map->messageCount() == 0) {
            $message = $this->t('New object was not saved, no error provided');
            $this->saveMessage($message);
            $this->message->display($message);
          }
        }
      }
      catch (MigrateException $e) {
        $this->migration->getIdMap()->saveIdMapping($row, array(), $e->getStatus(), $this->rollbackAction);
        $this->saveMessage($e->getMessage(), $e->getLevel());
        $this->message->display($e->getMessage());
      }
      catch (\Exception $e) {
        $this->migration->getIdMap()->saveIdMapping($row, array(), MigrateIdMapInterface::STATUS_FAILED, $this->rollbackAction);
        $this->handleException($e);
      }
      $this->total_processed++;
      $this->processed_since_feedback++;
      if ($highwater_property = $this->migration->get('highwaterProperty')) {
        $this->migration->saveHighwater($row->getSourceProperty($highwater_property['name']));
      }

      // Reset row properties.
      unset($sourceValues, $destinationValues);
      $this->sourceRowStatus = MigrateIdMapInterface::STATUS_IMPORTED;

      // TODO: Temporary. Remove when http://drupal.org/node/375494 is committed.
      // TODO: Should be done in MigrateDestinationEntity
      if (!empty($destination->entityType)) {
        entity_get_controller($destination->entityType)->resetCache();
      }

      if (($return = $this->checkStatus()) != MigrationInterface::RESULT_COMPLETED) {
        break;
      }
      if ($this->timeOptionExceeded()) {
        break;
      }
      try {
        $source->next();
      }
      catch (\Exception $e) {
        $this->message->display(
          $this->t('Migration failed with source plugin exception: !e',
            array('!e' => $e->getMessage())));
        return MigrationInterface::RESULT_FAILED;
      }
    }

    /**
     * @TODO uncomment this
     */
    #$this->progressMessage($return);

    return $return;
  }

  /**
   * @param Row $row
   *   The $row to be processed.
   * @param array $process
   *   A process pipeline configuration. If not set, the top level process
   *   configuration in the migration entity is used.
   * @param mixed $value
   *   Optional initial value of the pipeline for the first destination.
   *   Usually setting this is not necessary as $process typically starts with
   *   a 'get'. This is useful only when the $process contains a single
   *   destination and needs to access a value outside of the source. See
   *   \Drupal\migrate\Plugin\migrate\process\Iterator::transformKey for an
   *   example.
   */
  public function processRow(Row $row, array $process = NULL, $value = NULL) {
    foreach ($this->migration->getProcessPlugins($process) as $destination => $plugins) {
      foreach ($plugins as $plugin) {
        $value = $plugin->transform($value, $this, $row, $destination);
      }
      $row->setDestinationProperty($destination, $value);
      // Reset the value.
      $value = NULL;
    }
  }

  /**
   * Fetch the key array for the current source record.
   *
   * @return array
   */
  protected function currentSourceIds() {
    return $this->getSource()->getCurrentIds();
  }

  /**
   * Test whether we've exceeded the designated time limit.
   *
   * @return boolean
   *  TRUE if the threshold is exceeded, FALSE if not.
   */
  protected function timeOptionExceeded() {
    if (!$time_limit = $this->getTimeLimit()) {
      return FALSE;
    }
    $time_elapsed = time() - REQUEST_TIME;
    if ($time_elapsed >= $time_limit) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function getTimeLimit() {
    $limit = $this->migration->get('limit');
    if (isset($limit['unit']) && isset($limit['value']) && ($limit['unit'] == 'seconds' || $limit['unit'] == 'second')) {
      return $limit['value'];
    }
    else {
      return NULL;
    }
  }

  /**
   * Pass messages through to the map class.
   *
   * @param string $message
   *  The message to record.
   * @param int $level
   *  Optional message severity (defaults to MESSAGE_ERROR).
   */
  public function saveMessage($message, $level = MigrationInterface::MESSAGE_ERROR) {
    $this->migration->getIdMap()->saveMessage($this->sourceIdValues, $message, $level);
  }

  /**
   * Queue messages to be later saved through the map class.
   *
   * @param string $message
   *  The message to record.
   * @param int $level
   *  Optional message severity (defaults to MESSAGE_ERROR).
   */
  public function queueMessage($message, $level = MigrationInterface::MESSAGE_ERROR) {
    $this->queuedMessages[] = array('message' => $message, 'level' => $level);
  }

  /**
   * Save any messages we've queued up to the message table.
   */
  public function saveQueuedMessages() {
    foreach ($this->queuedMessages as $queued_message) {
      $this->saveMessage($queued_message['message'], $queued_message['level']);
    }
    $this->queuedMessages = array();
  }

  /**
   * Standard top-of-loop stuff, common between rollback and import - check
   * for exceptional conditions, and display feedback.
   */
  protected function checkStatus() {
    if ($this->memoryExceeded()) {
      return MigrationInterface::RESULT_INCOMPLETE;
    }
    if ($this->timeExceeded()) {
      return MigrationInterface::RESULT_INCOMPLETE;
    }
    /*
     * @TODO uncomment this
    if ($this->getStatus() == MigrationInterface::STATUS_STOPPING) {
      return MigrationBase::RESULT_STOPPED;
    }
    */
    // If feedback is requested, produce a progress message at the proper time
    /*
     * @TODO uncomment this
    if (isset($this->feedback)) {
      if (($this->feedback_unit == 'seconds' && time() - $this->lastfeedback >= $this->feedback) ||
          ($this->feedback_unit == 'items' && $this->processed_since_feedback >= $this->feedback)) {
        $this->progressMessage(MigrationInterface::RESULT_INCOMPLETE);
      }
    }
    */

    return MigrationInterface::RESULT_COMPLETED;
  }

  /**
   * Test whether we've exceeded the desired memory threshold. If so, output a message.
   *
   * @return boolean
   *  TRUE if the threshold is exceeded, FALSE if not.
   */
  protected function memoryExceeded() {
    $usage = memory_get_usage();
    $pct_memory = $usage / $this->memoryLimit;
    if ($pct_memory > $this->memoryThreshold) {
      $this->message->display(
        $this->t('Memory usage is !usage (!pct% of limit !limit), resetting statics',
          array('!pct' => round($pct_memory*100),
                '!usage' => format_size($usage),
                '!limit' => format_size($this->memoryLimit))),
        'warning');
      // First, try resetting Drupal's static storage - this frequently releases
      // plenty of memory to continue
      drupal_static_reset();
      $usage = memory_get_usage();
      $pct_memory = $usage/$this->memoryLimit;
      // Use a lower threshold - we don't want to be in a situation where we keep
      // coming back here and trimming a tiny amount
      if ($pct_memory > (.90 * $this->memoryThreshold)) {
        $this->message->display(
          $this->t('Memory usage is now !usage (!pct% of limit !limit), not enough reclaimed, starting new batch',
            array('!pct' => round($pct_memory*100),
                  '!usage' => format_size($usage),
                  '!limit' => format_size($this->memoryLimit))),
          'warning');
        return TRUE;
      }
      else {
        $this->message->display(
          $this->t('Memory usage is now !usage (!pct% of limit !limit), reclaimed enough, continuing',
            array('!pct' => round($pct_memory*100),
                  '!usage' => format_size($usage),
                  '!limit' => format_size($this->memoryLimit))),
          'warning');
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Test whether we're approaching the PHP time limit.
   *
   * @return boolean
   *  TRUE if the threshold is exceeded, FALSE if not.
   */
  protected function timeExceeded() {
    if ($this->timeLimit == 0) {
      return FALSE;
    }
    $time_elapsed = time() - REQUEST_TIME;
    $pct_time = $time_elapsed / $this->timeLimit;
    if ($pct_time > $this->timeThreshold) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Takes an Exception object and both saves and displays it, pulling additional
   * information on the location triggering the exception.
   *
   * @param \Exception $exception
   *  Object representing the exception.
   * @param boolean $save
   *  Whether to save the message in the migration's mapping table. Set to FALSE
   *  in contexts where this doesn't make sense.
   */
  public function handleException($exception, $save = TRUE) {
    $result = _drupal_decode_exception($exception);
    $message = $result['!message'] . ' (' . $result['%file'] . ':' . $result['%line'] . ')';
    if ($save) {
      $this->saveMessage($message);
    }
    $this->message->display($message);
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager()->translate($string, $args, $options);
  }

  /**
   * Gets the translation manager.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation manager.
   */
  protected function translationManager() {
    if (!$this->translationManager) {
      $this->translationManager = \Drupal::translation();
    }
    return $this->translationManager;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\Core\Config\BatchConfigImporter.
 */

namespace Drupal\Core\Config;

/**
 * Defines a batch configuration importer.
 *
 * @see \Drupal\Core\Config\ConfigImporter
 */
class BatchConfigImporter extends ConfigImporter {

  /**
   * Initializes the config importer in preparation for processing a batch.
   */
  public function initialize() {
    // Ensure that the changes have been validated.
    $this->validate();

    if (!$this->lock->acquire(static::LOCK_ID)) {
      // Another process is synchronizing configuration.
      throw new ConfigImporterException(sprintf('%s is already importing', static::LOCK_ID));
    }
    $this->totalToProcess = 0;
    foreach(array('create', 'delete', 'update') as $op) {
      $this->totalToProcess += count($this->getUnprocessed($op));
    }
  }

  /**
   * Processes batch.
   *
   * @param array $context.
   *   The batch context.
   */
  public function processBatch(array &$context) {
    $operation = $this->getNextOperation();
    if (!empty($operation)) {
      $this->process($operation['op'], $operation['name']);
      $context['message'] = t('Synchronizing @name.', array('@name' => $operation['name']));
      $context['finished'] = $this->batchProgress();
    }
    else {
      $context['finished'] = 1;
    }
    if ($context['finished'] >= 1) {
      $this->eventDispatcher->dispatch(ConfigEvents::IMPORT, new ConfigImporterEvent($this));
      // The import is now complete.
      $this->lock->release(static::LOCK_ID);
      $this->reset();
    }
  }

  /**
   * Gets percentage of progress made.
   *
   * @return float
   *   The percentage of progress made expressed as a float between 0 and 1.
   */
  protected  function batchProgress() {
    $processed_count = count($this->processed['create']) + count($this->processed['delete']) + count($this->processed['update']);
    return $processed_count / $this->totalToProcess;
  }

  /**
   * Gets the next operation to perform.
   *
   * @return array|bool
   *   An array containing the next operation and configuration name to perform
   *   it on. If there is nothing left to do returns FALSE;
   */
  protected  function getNextOperation() {
    foreach(array('create', 'delete', 'update') as $op) {
      $names = $this->getUnprocessed($op);
      if (!empty($names)) {
        return array(
          'op' => $op,
          'name' => array_shift($names),
        );
      }
    }
    return FALSE;
  }
}

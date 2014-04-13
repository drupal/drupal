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
   * The total number of extensions to process.
   *
   * @var int
   */
  protected $totalExtensionsToProcess = 0;

  /**
   * The total number of configuration objects to process.
   *
   * @var int
   */
  protected $totalConfigurationToProcess = 0;

  /**
   * Initializes the config importer in preparation for processing a batch.
   *
   * @return array
   *   An array of method names that to be called by the batch. If there are
   *   modules or themes to process then an extra step is added.
   *
   * @throws ConfigImporterException
   *   If the configuration is already importing.
   */
  public function initialize() {
    $batch_operations = array();
    $this->createExtensionChangelist();

    // Ensure that the changes have been validated.
    $this->validate();

    if (!$this->lock->acquire(static::LOCK_ID)) {
      // Another process is synchronizing configuration.
      throw new ConfigImporterException(sprintf('%s is already importing', static::LOCK_ID));
    }

    $modules = $this->getUnprocessedExtensions('module');
    foreach (array('install', 'uninstall') as $op) {
      $this->totalExtensionsToProcess += count($modules[$op]);
    }
    $themes = $this->getUnprocessedExtensions('theme');
    foreach (array('enable', 'disable') as $op) {
      $this->totalExtensionsToProcess += count($themes[$op]);
    }

    // We have extensions to process.
    if ($this->totalExtensionsToProcess > 0) {
      $batch_operations[] = 'processExtensionBatch';
    }

    $batch_operations[] = 'processConfigurationBatch';
    $batch_operations[] = 'finishBatch';
    return $batch_operations;
  }

  /**
   * Processes extensions as a batch operation.
   *
   * @param array $context.
   *   The batch context.
   */
  public function processExtensionBatch(array &$context) {
    $operation = $this->getNextExtensionOperation();
    if (!empty($operation)) {
      $this->processExtension($operation['type'], $operation['op'], $operation['name']);
      $context['message'] = t('Synchronising extensions: @op @name.', array('@op' => $operation['op'], '@name' => $operation['name']));
      $processed_count = count($this->processedExtensions['module']['install']) + count($this->processedExtensions['module']['uninstall']);
      $processed_count += count($this->processedExtensions['theme']['disable']) + count($this->processedExtensions['theme']['enable']);
      $context['finished'] = $processed_count / $this->totalExtensionsToProcess;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Processes configuration as a batch operation.
   *
   * @param array $context.
   *   The batch context.
   */
  public function processConfigurationBatch(array &$context) {
    // The first time this is called we need to calculate the total to process.
    // This involves recalculating the changelist which will ensure that if
    // extensions have been processed any configuration affected will be taken
    // into account.
    if ($this->totalConfigurationToProcess == 0) {
      $this->storageComparer->reset();
      foreach (array('delete', 'create', 'update') as $op) {
        $this->totalConfigurationToProcess += count($this->getUnprocessedConfiguration($op));
      }
    }
    $operation = $this->getNextConfigurationOperation();
    if (!empty($operation)) {
      if ($this->checkOp($operation['op'], $operation['name'])) {
        $this->processConfiguration($operation['op'], $operation['name']);
      }
      $context['message'] = t('Synchronizing configuration: @op @name.', array('@op' => $operation['op'], '@name' => $operation['name']));
      $processed_count = count($this->processedConfiguration['create']) + count($this->processedConfiguration['delete']) + count($this->processedConfiguration['update']);
      $context['finished'] = $processed_count / $this->totalConfigurationToProcess;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Finishes the batch.
   *
   * @param array $context.
   *   The batch context.
   */
  public function finishBatch(array &$context) {
    $this->eventDispatcher->dispatch(ConfigEvents::IMPORT, new ConfigImporterEvent($this));
    // The import is now complete.
    $this->lock->release(static::LOCK_ID);
    $this->reset();
    $context['message'] = t('Finalising configuration synchronisation.');
    $context['finished'] = 1;
  }

  /**
   * Gets the next extension operation to perform.
   *
   * @return array|bool
   *   An array containing the next operation and extension name to perform it
   *   on. If there is nothing left to do returns FALSE;
   */
  protected function getNextExtensionOperation() {
    foreach (array('install', 'uninstall') as $op) {
      $modules = $this->getUnprocessedExtensions('module');
      if (!empty($modules[$op])) {
        return array(
          'op' => $op,
          'type' => 'module',
          'name' => array_shift($modules[$op]),
        );
      }
    }
    foreach (array('enable', 'disable') as $op) {
      $themes = $this->getUnprocessedExtensions('theme');
      if (!empty($themes[$op])) {
        return array(
          'op' => $op,
          'type' => 'theme',
          'name' => array_shift($themes[$op]),
        );
      }
    }
    return FALSE;
  }

  /**
   * Gets the next configuration operation to perform.
   *
   * @return array|bool
   *   An array containing the next operation and configuration name to perform
   *   it on. If there is nothing left to do returns FALSE;
   */
  protected function getNextConfigurationOperation() {
    // The order configuration operations is processed is important. Deletes
    // have to come first so that recreates can work.
    foreach (array('delete', 'create', 'update') as $op) {
      $config_names = $this->getUnprocessedConfiguration($op);
      if (!empty($config_names)) {
        return array(
          'op' => $op,
          'name' => array_shift($config_names),
        );
      }
    }
    return FALSE;
  }
}

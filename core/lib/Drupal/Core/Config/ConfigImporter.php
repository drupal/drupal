<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\Importer\MissingContentEvent;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a configuration importer.
 *
 * A config importer imports the changes into the configuration system. To
 * determine which changes to import a StorageComparer in used.
 *
 * @see \Drupal\Core\Config\StorageComparerInterface
 *
 * The ConfigImporter has an identifier which is used to construct event names.
 * The events fired during an import are:
 * - ConfigEvents::IMPORT_VALIDATE: Events listening can throw a
 *   \Drupal\Core\Config\ConfigImporterException to prevent an import from
 *   occurring.
 *   @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
 * - ConfigEvents::IMPORT: Events listening can react to a successful import.
 *   @see \Drupal\Core\EventSubscriber\ConfigSnapshotSubscriber
 *
 * @see \Drupal\Core\Config\ConfigImporterEvent
 */
class ConfigImporter {
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The name used to identify the lock.
   */
  const LOCK_NAME = 'config_importer';

  /**
   * The storage comparer used to discover configuration changes.
   *
   * @var \Drupal\Core\Config\StorageComparerInterface
   */
  protected $storageComparer;

  /**
   * The event dispatcher used to notify subscribers.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * List of configuration file changes processed by the import().
   *
   * @var array
   */
  protected $processedConfiguration;

  /**
   * List of extension changes processed by the import().
   *
   * @var array
   */
  protected $processedExtensions;

  /**
   * List of extension changes to be processed by the import().
   *
   * @var array
   */
  protected $extensionChangelist;

  /**
   * Indicates changes to import have been validated.
   *
   * @var bool
   */
  protected $validated;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Flag set to import system.theme during processing theme install and uninstalls.
   *
   * @var bool
   */
  protected $processedSystemTheme = FALSE;

  /**
   * A log of any errors encountered.
   *
   * If errors are logged during the validation event the configuration
   * synchronization will not occur. If errors occur during an import then best
   * efforts are made to complete the synchronization.
   *
   * @var array
   */
  protected $errors = [];

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
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * Constructs a configuration import object.
   *
   * @param \Drupal\Core\Config\StorageComparerInterface $storage_comparer
   *   A storage comparer object used to determine configuration changes and
   *   access the source and target storage objects.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher used to notify subscribers of config import events.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend to ensure multiple imports do not occur at the same time.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $extension_list_theme
   *   The theme extension list.
   */
  public function __construct(StorageComparerInterface $storage_comparer, EventDispatcherInterface $event_dispatcher, ConfigManagerInterface $config_manager, LockBackendInterface $lock, TypedConfigManagerInterface $typed_config, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, ThemeHandlerInterface $theme_handler, TranslationInterface $string_translation, ModuleExtensionList $extension_list_module, ThemeExtensionList $extension_list_theme = NULL) {
    $this->moduleExtensionList = $extension_list_module;
    $this->storageComparer = $storage_comparer;
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->lock = $lock;
    $this->typedConfigManager = $typed_config;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->themeHandler = $theme_handler;
    $this->stringTranslation = $string_translation;
    if ($extension_list_theme === NULL) {
      $extension_list_theme = \Drupal::service('extension.list.theme');
    }
    $this->themeExtensionList = $extension_list_theme;
    foreach ($this->storageComparer->getAllCollectionNames() as $collection) {
      $this->processedConfiguration[$collection] = $this->storageComparer->getEmptyChangelist();
    }
    $this->processedExtensions = $this->getEmptyExtensionsProcessedList();
  }

  /**
   * Logs an error message.
   *
   * @param string $message
   *   The message to log.
   */
  public function logError($message) {
    $this->errors[] = $message;
  }

  /**
   * Returns error messages created while running the import.
   *
   * @return array
   *   List of messages.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Gets the configuration storage comparer.
   *
   * @return \Drupal\Core\Config\StorageComparerInterface
   *   Storage comparer object used to calculate configuration changes.
   */
  public function getStorageComparer() {
    return $this->storageComparer;
  }

  /**
   * Resets the storage comparer and processed list.
   *
   * @return $this
   *   The ConfigImporter instance.
   */
  public function reset() {
    $this->storageComparer->reset();
    // Empty all the lists.
    foreach ($this->storageComparer->getAllCollectionNames() as $collection) {
      $this->processedConfiguration[$collection] = $this->storageComparer->getEmptyChangelist();
    }
    $this->extensionChangelist = $this->processedExtensions = $this->getEmptyExtensionsProcessedList();

    $this->validated = FALSE;
    $this->processedSystemTheme = FALSE;
    return $this;
  }

  /**
   * Gets an empty list of extensions to process.
   *
   * @return array
   *   An empty list of extensions to process.
   */
  protected function getEmptyExtensionsProcessedList() {
    return [
      'module' => [
        'install' => [],
        'uninstall' => [],
      ],
      'theme' => [
        'install' => [],
        'uninstall' => [],
      ],
    ];
  }

  /**
   * Checks if there are any unprocessed configuration changes.
   *
   * @return bool
   *   TRUE if there are changes to process and FALSE if not.
   */
  public function hasUnprocessedConfigurationChanges() {
    foreach ($this->storageComparer->getAllCollectionNames() as $collection) {
      foreach (['delete', 'create', 'rename', 'update'] as $op) {
        if (count($this->getUnprocessedConfiguration($op, $collection))) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Gets list of processed changes.
   *
   * @param string $collection
   *   (optional) The configuration collection to get processed changes for.
   *   Defaults to the default collection.
   *
   * @return array
   *   An array containing a list of processed changes.
   */
  public function getProcessedConfiguration($collection = StorageInterface::DEFAULT_COLLECTION) {
    return $this->processedConfiguration[$collection];
  }

  /**
   * Sets a change as processed.
   *
   * @param string $collection
   *   The configuration collection to set a change as processed for.
   * @param string $op
   *   The change operation performed, either delete, create, rename, or update.
   * @param string $name
   *   The name of the configuration processed.
   */
  protected function setProcessedConfiguration($collection, $op, $name) {
    $this->processedConfiguration[$collection][$op][] = $name;
  }

  /**
   * Gets a list of unprocessed changes for a given operation.
   *
   * @param string $op
   *   The change operation to get the unprocessed list for, either delete,
   *   create, rename, or update.
   * @param string $collection
   *   (optional) The configuration collection to get unprocessed changes for.
   *   Defaults to the default collection.
   *
   * @return array
   *   An array of configuration names.
   */
  public function getUnprocessedConfiguration($op, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return array_diff($this->storageComparer->getChangelist($op, $collection), $this->processedConfiguration[$collection][$op]);
  }

  /**
   * Gets list of processed extension changes.
   *
   * @return array
   *   An array containing a list of processed extension changes.
   */
  public function getProcessedExtensions() {
    return $this->processedExtensions;
  }

  /**
   * Sets an extension change as processed.
   *
   * @param string $type
   *   The type of extension, either 'theme' or 'module'.
   * @param string $op
   *   The change operation performed, either install or uninstall.
   * @param string $name
   *   The name of the extension processed.
   */
  protected function setProcessedExtension($type, $op, $name) {
    $this->processedExtensions[$type][$op][] = $name;
  }

  /**
   * Populates the extension change list.
   */
  protected function createExtensionChangelist() {
    // Create an empty changelist.
    $this->extensionChangelist = $this->getEmptyExtensionsProcessedList();

    // Read the extensions information to determine changes.
    $current_extensions = $this->storageComparer->getTargetStorage()->read('core.extension');
    $new_extensions = $this->storageComparer->getSourceStorage()->read('core.extension');

    // If there is no extension information in sync then exit. This is probably
    // due to an empty sync directory.
    if (!$new_extensions) {
      return;
    }

    // Reset the module list in case a stale cache item has been set by another
    // process during deployment.
    $this->moduleExtensionList->reset();
    // Get a list of modules with dependency weights as values.
    $module_data = $this->moduleExtensionList->getList();
    // Use the actual module weights.
    $module_list = array_combine(array_keys($module_data), array_keys($module_data));
    $module_list = array_map(function ($module) use ($module_data) {
      return $module_data[$module]->sort;
    }, $module_list);

    // Determine which modules to uninstall.
    $uninstall = array_keys(array_diff_key($current_extensions['module'], $new_extensions['module']));
    // Sort the list of newly uninstalled extensions by their weights, so that
    // dependencies are uninstalled last. Extensions of the same weight are
    // sorted in reverse alphabetical order, to ensure the order is exactly
    // opposite from installation. For example, this module list:
    // @code
    // array(
    //   'actions' => 0,
    //   'ban' => 0,
    //   'options' => -2,
    //   'text' => -1,
    // );
    // @endcode
    // will result in the following sort order:
    // 1. -2   options
    // 2. -1   text
    // 3.  0 0 ban
    // 4.  0 1 actions
    // @todo Move this sorting functionality to the extension system.
    array_multisort(array_values($module_list), SORT_ASC, array_keys($module_list), SORT_DESC, $module_list);
    $this->extensionChangelist['module']['uninstall'] = array_intersect(array_keys($module_list), $uninstall);

    // Determine which modules to install.
    $install = array_keys(array_diff_key($new_extensions['module'], $current_extensions['module']));
    // Ensure that installed modules are sorted in exactly the reverse order
    // (with dependencies installed first, and modules of the same weight sorted
    // in alphabetical order).
    $module_list = array_reverse($module_list);
    $this->extensionChangelist['module']['install'] = array_intersect(array_keys($module_list), $install);

    // If we're installing the install profile ensure it comes last. This will
    // occur when installing a site from configuration.
    $install_profile_key = array_search($new_extensions['profile'], $this->extensionChangelist['module']['install'], TRUE);
    if ($install_profile_key !== FALSE) {
      unset($this->extensionChangelist['module']['install'][$install_profile_key]);
      $this->extensionChangelist['module']['install'][] = $new_extensions['profile'];
    }

    // Get a list of themes with dependency weights as values.
    $theme_data = $this->themeExtensionList->getList();
    // Use the actual theme weights.
    $theme_list = array_combine(array_keys($theme_data), array_keys($theme_data));
    $theme_list = array_map(function ($theme) use ($theme_data) {
      return $theme_data[$theme]->sort;
    }, $theme_list);
    array_multisort(array_values($theme_list), SORT_ASC, array_keys($theme_list), SORT_DESC, $theme_list);

    // Work out what themes to install and to uninstall.
    $uninstall = array_keys(array_diff_key($current_extensions['theme'], $new_extensions['theme']));
    $this->extensionChangelist['theme']['uninstall'] = array_intersect(array_keys($theme_list), $uninstall);
    // Ensure that installed themes are sorted in exactly the reverse order
    // (with dependencies installed first, and themes of the same weight sorted
    // in alphabetical order).
    $install = array_keys(array_diff_key($new_extensions['theme'], $current_extensions['theme']));
    $theme_list = array_reverse($theme_list);
    $this->extensionChangelist['theme']['install'] = array_intersect(array_keys($theme_list), $install);
  }

  /**
   * Gets a list changes for extensions.
   *
   * @param string $type
   *   The type of extension, either 'theme' or 'module'.
   * @param string $op
   *   The change operation to get the unprocessed list for, either install
   *   or uninstall.
   *
   * @return array
   *   An array of extension names.
   */
  public function getExtensionChangelist($type, $op = NULL) {
    if ($op) {
      return $this->extensionChangelist[$type][$op];
    }
    return $this->extensionChangelist[$type];
  }

  /**
   * Gets a list of unprocessed changes for extensions.
   *
   * @param string $type
   *   The type of extension, either 'theme' or 'module'.
   *
   * @return array
   *   An array of extension names.
   */
  protected function getUnprocessedExtensions($type) {
    $changelist = $this->getExtensionChangelist($type);
    return [
      'install' => array_diff($changelist['install'], $this->processedExtensions[$type]['install']),
      'uninstall' => array_diff($changelist['uninstall'], $this->processedExtensions[$type]['uninstall']),
    ];
  }

  /**
   * Imports the changelist to the target storage.
   *
   * @return $this
   *   The ConfigImporter instance.
   *
   * @throws \Drupal\Core\Config\ConfigException
   */
  public function import() {
    if ($this->hasUnprocessedConfigurationChanges()) {
      $sync_steps = $this->initialize();

      foreach ($sync_steps as $step) {
        $context = [];
        do {
          $this->doSyncStep($step, $context);
        } while ($context['finished'] < 1);
      }
    }
    return $this;
  }

  /**
   * Calls a config import step.
   *
   * @param string|callable $sync_step
   *   The step to do. Either a method on the ConfigImporter class or a
   *   callable.
   * @param array $context
   *   A batch context array. If the config importer is not running in a batch
   *   the only array key that is used is $context['finished']. A process needs
   *   to set $context['finished'] = 1 when it is done.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown if the $sync_step can not be called.
   */
  public function doSyncStep($sync_step, &$context) {
    if (is_string($sync_step) && method_exists($this, $sync_step)) {
      \Drupal::service('config.installer')->setSyncing(TRUE);
      $this->$sync_step($context);
    }
    elseif (is_callable($sync_step)) {
      \Drupal::service('config.installer')->setSyncing(TRUE);
      $sync_step($context, $this);
    }
    else {
      throw new \InvalidArgumentException('Invalid configuration synchronization step');
    }
    \Drupal::service('config.installer')->setSyncing(FALSE);
  }

  /**
   * Initializes the config importer in preparation for processing a batch.
   *
   * @return array
   *   An array of \Drupal\Core\Config\ConfigImporter method names and callables
   *   that are invoked to complete the import. If there are modules or themes
   *   to process then an extra step is added.
   *
   * @throws \Drupal\Core\Config\ConfigImporterException
   *   If the configuration is already importing.
   */
  public function initialize() {
    // Ensure that the changes have been validated.
    $this->validate();

    if (!$this->lock->acquire(static::LOCK_NAME)) {
      // Another process is synchronizing configuration.
      throw new ConfigImporterException(sprintf('%s is already importing', static::LOCK_NAME));
    }

    $sync_steps = [];
    $modules = $this->getUnprocessedExtensions('module');
    foreach (['install', 'uninstall'] as $op) {
      $this->totalExtensionsToProcess += count($modules[$op]);
    }
    $themes = $this->getUnprocessedExtensions('theme');
    foreach (['install', 'uninstall'] as $op) {
      $this->totalExtensionsToProcess += count($themes[$op]);
    }

    // We have extensions to process.
    if ($this->totalExtensionsToProcess > 0) {
      $sync_steps[] = 'processExtensions';
    }
    $sync_steps[] = 'processConfigurations';
    $sync_steps[] = 'processMissingContent';
    // Allow modules to add new steps to configuration synchronization.
    $this->moduleHandler->alter('config_import_steps', $sync_steps, $this);
    $sync_steps[] = 'finish';
    return $sync_steps;
  }

  /**
   * Processes extensions as a batch operation.
   *
   * @param array|\ArrayAccess $context
   *   The batch context.
   */
  protected function processExtensions(&$context) {
    $operation = $this->getNextExtensionOperation();
    if (!empty($operation)) {
      $this->processExtension($operation['type'], $operation['op'], $operation['name']);
      $context['message'] = t('Synchronizing extensions: @op @name.', ['@op' => $operation['op'], '@name' => $operation['name']]);
      $processed_count = count($this->processedExtensions['module']['install']) + count($this->processedExtensions['module']['uninstall']);
      $processed_count += count($this->processedExtensions['theme']['uninstall']) + count($this->processedExtensions['theme']['install']);
      $context['finished'] = $processed_count / $this->totalExtensionsToProcess;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Processes configuration as a batch operation.
   *
   * @param array|\ArrayAccess $context
   *   The batch context.
   */
  protected function processConfigurations(&$context) {
    // The first time this is called we need to calculate the total to process.
    // This involves recalculating the changelist which will ensure that if
    // extensions have been processed any configuration affected will be taken
    // into account.
    if ($this->totalConfigurationToProcess == 0) {
      $this->storageComparer->reset();
      foreach ($this->storageComparer->getAllCollectionNames() as $collection) {
        foreach (['delete', 'create', 'rename', 'update'] as $op) {
          $this->totalConfigurationToProcess += count($this->getUnprocessedConfiguration($op, $collection));
        }
      }

      // Adjust the totals for system.theme.
      // @see \Drupal\Core\Config\ConfigImporter::processExtension
      if ($this->processedSystemTheme) {
        $this->totalConfigurationToProcess++;
      }
    }
    $operation = $this->getNextConfigurationOperation();
    if (!empty($operation)) {
      if ($this->checkOp($operation['collection'], $operation['op'], $operation['name'])) {
        $this->processConfiguration($operation['collection'], $operation['op'], $operation['name']);
      }
      if ($operation['collection'] == StorageInterface::DEFAULT_COLLECTION) {
        $context['message'] = $this->t('Synchronizing configuration: @op @name.', ['@op' => $operation['op'], '@name' => $operation['name']]);
      }
      else {
        $context['message'] = $this->t('Synchronizing configuration: @op @name in @collection.', ['@op' => $operation['op'], '@name' => $operation['name'], '@collection' => $operation['collection']]);
      }
      $processed_count = 0;
      foreach ($this->storageComparer->getAllCollectionNames() as $collection) {
        foreach (['delete', 'create', 'rename', 'update'] as $op) {
          $processed_count += count($this->processedConfiguration[$collection][$op]);
        }
      }
      $context['finished'] = $processed_count / $this->totalConfigurationToProcess;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Handles processing of missing content.
   *
   * @param array|\ArrayAccess $context
   *   Standard batch context.
   */
  protected function processMissingContent(&$context) {
    $sandbox = &$context['sandbox']['config'];
    if (!isset($sandbox['missing_content'])) {
      $missing_content = $this->configManager->findMissingContentDependencies();
      $sandbox['missing_content']['data'] = $missing_content;
      $sandbox['missing_content']['total'] = count($missing_content);
    }
    else {
      $missing_content = $sandbox['missing_content']['data'];
    }
    if (!empty($missing_content)) {
      $event = new MissingContentEvent($missing_content);
      // Fire an event to allow listeners to create the missing content.
      $this->eventDispatcher->dispatch($event, ConfigEvents::IMPORT_MISSING_CONTENT);
      $sandbox['missing_content']['data'] = $event->getMissingContent();
    }
    $current_count = count($sandbox['missing_content']['data']);
    if ($current_count) {
      $context['message'] = $this->t('Resolving missing content');
      $context['finished'] = ($sandbox['missing_content']['total'] - $current_count) / $sandbox['missing_content']['total'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Finishes the batch.
   *
   * @param array|\ArrayAccess $context
   *   The batch context.
   */
  protected function finish(&$context) {
    $this->eventDispatcher->dispatch(new ConfigImporterEvent($this), ConfigEvents::IMPORT);
    // The import is now complete.
    $this->lock->release(static::LOCK_NAME);
    $this->reset();
    $context['message'] = t('Finalizing configuration synchronization.');
    $context['finished'] = 1;
  }

  /**
   * Gets the next extension operation to perform.
   *
   * Uninstalls are processed first with themes coming before modules. Then
   * installs are processed with modules coming before themes. This order is
   * necessary because themes can depend on modules.
   *
   * @return array|bool
   *   An array containing the next operation and extension name to perform it
   *   on. If there is nothing left to do returns FALSE;
   */
  protected function getNextExtensionOperation() {
    foreach (['uninstall', 'install'] as $op) {
      $types = $op === 'uninstall' ? ['theme', 'module'] : ['module', 'theme'];
      foreach ($types as $type) {
        $unprocessed = $this->getUnprocessedExtensions($type);
        if (!empty($unprocessed[$op])) {
          return [
            'op' => $op,
            'type' => $type,
            'name' => array_shift($unprocessed[$op]),
          ];
        }
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
    foreach ($this->storageComparer->getAllCollectionNames() as $collection) {
      foreach (['delete', 'create', 'rename', 'update'] as $op) {
        $config_names = $this->getUnprocessedConfiguration($op, $collection);
        if (!empty($config_names)) {
          return [
            'op' => $op,
            'name' => array_shift($config_names),
            'collection' => $collection,
          ];
        }
      }
    }
    return FALSE;
  }

  /**
   * Dispatches validate event for a ConfigImporter object.
   *
   * Events should throw a \Drupal\Core\Config\ConfigImporterException to
   * prevent an import from occurring.
   *
   * @throws \Drupal\Core\Config\ConfigImporterException
   *   Exception thrown if the validate event logged any errors.
   */
  public function validate() {
    if (!$this->validated) {
      $this->errors = [];
      // Create the list of installs and uninstalls.
      $this->createExtensionChangelist();
      // Validate renames.
      foreach ($this->getUnprocessedConfiguration('rename') as $name) {
        $names = $this->storageComparer->extractRenameNames($name);
        $old_entity_type_id = $this->configManager->getEntityTypeIdByName($names['old_name']);
        $new_entity_type_id = $this->configManager->getEntityTypeIdByName($names['new_name']);
        if ($old_entity_type_id != $new_entity_type_id) {
          $this->logError($this->t('Entity type mismatch on rename. @old_type not equal to @new_type for existing configuration @old_name and staged configuration @new_name.', ['@old_type' => $old_entity_type_id, '@new_type' => $new_entity_type_id, '@old_name' => $names['old_name'], '@new_name' => $names['new_name']]));
        }
        // Has to be a configuration entity.
        if (!$old_entity_type_id) {
          $this->logError($this->t('Rename operation for simple configuration. Existing configuration @old_name and staged configuration @new_name.', ['@old_name' => $names['old_name'], '@new_name' => $names['new_name']]));
        }
      }
      $this->eventDispatcher->dispatch(new ConfigImporterEvent($this), ConfigEvents::IMPORT_VALIDATE);
      if (count($this->getErrors())) {
        $errors = array_merge(['There were errors validating the config synchronization.'], $this->getErrors());
        throw new ConfigImporterException(implode(PHP_EOL, $errors));
      }
      else {
        $this->validated = TRUE;
      }
    }
    return $this;
  }

  /**
   * Processes a configuration change.
   *
   * @param string $collection
   *   The configuration collection to process changes for.
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the configuration to process.
   *
   * @throws \Exception
   *   Thrown when the import process fails, only thrown when no importer log is
   *   set, otherwise the exception message is logged and the configuration
   *   is skipped.
   */
  protected function processConfiguration($collection, $op, $name) {
    try {
      $processed = FALSE;
      if ($collection == StorageInterface::DEFAULT_COLLECTION) {
        $processed = $this->importInvokeOwner($collection, $op, $name);
      }
      if (!$processed) {
        $this->importConfig($collection, $op, $name);
      }
    }
    catch (\Exception $e) {
      $this->logError($this->t('Unexpected error during import with operation @op for @name: @message', ['@op' => $op, '@name' => $name, '@message' => $e->getMessage()]));
      // Error for that operation was logged, mark it as processed so that
      // the import can continue.
      $this->setProcessedConfiguration($collection, $op, $name);
    }
  }

  /**
   * Processes an extension change.
   *
   * @param string $type
   *   The type of extension, either 'module' or 'theme'.
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the extension to process.
   */
  protected function processExtension($type, $op, $name) {
    // Set the config installer to use the sync directory instead of the
    // extensions own default config directories.
    \Drupal::service('config.installer')
      ->setSourceStorage($this->storageComparer->getSourceStorage());
    if ($type == 'module') {
      $this->moduleInstaller->$op([$name], FALSE);
      // Installing a module can cause a kernel boot therefore reinject all the
      // services.
      $this->reInjectMe();
      // During a module install or uninstall the container is rebuilt and the
      // module handler is called. This causes the container's instance of the
      // module handler not to have loaded all the enabled modules.
      $this->moduleHandler->loadAll();
    }
    if ($type == 'theme') {
      // Theme uninstalls possible remove default or admin themes therefore we
      // need to import this before doing any. If there are no uninstalls and
      // the default or admin theme is changing this will be picked up whilst
      // processing configuration.
      if ($op == 'uninstall' && $this->processedSystemTheme === FALSE) {
        $this->importConfig(StorageInterface::DEFAULT_COLLECTION, 'update', 'system.theme');
        $this->configManager->getConfigFactory()->reset('system.theme');
        $this->processedSystemTheme = TRUE;
      }
      \Drupal::service('theme_installer')->$op([$name]);
    }

    $this->setProcessedExtension($type, $op, $name);
  }

  /**
   * Checks that the operation is still valid.
   *
   * During a configuration import secondary writes and deletes are possible.
   * This method checks that the operation is still valid before processing a
   * configuration change.
   *
   * @param string $collection
   *   The configuration collection.
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the configuration to process.
   *
   * @return bool
   *   TRUE is to continue processing, FALSE otherwise.
   *
   * @throws \Drupal\Core\Config\ConfigImporterException
   */
  protected function checkOp($collection, $op, $name) {
    if ($op == 'rename') {
      $names = $this->storageComparer->extractRenameNames($name);
      $target_exists = $this->storageComparer->getTargetStorage($collection)->exists($names['new_name']);
      if ($target_exists) {
        // If the target exists, the rename has already occurred as the
        // result of a secondary configuration write. Change the operation
        // into an update. This is the desired behavior since renames often
        // have to occur together. For example, renaming a node type must
        // also result in renaming its fields and entity displays.
        $this->storageComparer->moveRenameToUpdate($name);
        return FALSE;
      }
      return TRUE;
    }
    $target_exists = $this->storageComparer->getTargetStorage($collection)->exists($name);
    switch ($op) {
      case 'delete':
        if (!$target_exists) {
          // The configuration has already been deleted. For example, a field
          // is automatically deleted if all the instances are.
          $this->setProcessedConfiguration($collection, $op, $name);
          return FALSE;
        }
        break;

      case 'create':
        if ($target_exists) {
          // If the target already exists, use the entity storage to delete it
          // again, if is a simple config, delete it directly.
          if ($entity_type_id = $this->configManager->getEntityTypeIdByName($name)) {
            $entity_storage = $this->configManager->getEntityTypeManager()->getStorage($entity_type_id);
            $entity_type = $this->configManager->getEntityTypeManager()->getDefinition($entity_type_id);
            $entity = $entity_storage->load($entity_storage->getIDFromConfigName($name, $entity_type->getConfigPrefix()));
            $entity->delete();
            $this->logError($this->t('Deleted and replaced configuration entity "@name"', ['@name' => $name]));
          }
          else {
            $this->storageComparer->getTargetStorage($collection)->delete($name);
            $this->logError($this->t('Deleted and replaced configuration "@name"', ['@name' => $name]));
          }
          return TRUE;
        }
        break;

      case 'update':
        if (!$target_exists) {
          $this->logError($this->t('Update target "@name" is missing.', ['@name' => $name]));
          // Mark as processed so that the synchronization continues. Once the
          // the current synchronization is complete it will show up as a
          // create.
          $this->setProcessedConfiguration($collection, $op, $name);
          return FALSE;
        }
        break;
    }
    return TRUE;
  }

  /**
   * Writes a configuration change from the source to the target storage.
   *
   * @param string $collection
   *   The configuration collection.
   * @param string $op
   *   The change operation.
   * @param string $name
   *   The name of the configuration to process.
   */
  protected function importConfig($collection, $op, $name) {
    // Allow config factory overriders to use a custom configuration object if
    // they are responsible for the collection.
    $overrider = $this->configManager->getConfigCollectionInfo()->getOverrideService($collection);
    if ($overrider) {
      $config = $overrider->createConfigObject($name, $collection);
    }
    else {
      $config = new Config($name, $this->storageComparer->getTargetStorage($collection), $this->eventDispatcher, $this->typedConfigManager);
    }
    if ($op == 'delete') {
      $config->delete();
    }
    else {
      $data = $this->storageComparer->getSourceStorage($collection)->read($name);
      $config->setData($data ? $data : []);
      $config->save();
    }
    $this->setProcessedConfiguration($collection, $op, $name);
  }

  /**
   * Invokes import* methods on configuration entity storage.
   *
   * Allow modules to take over configuration change operations for higher-level
   * configuration data.
   *
   * @todo Add support for other extension types; e.g., themes etc.
   *
   * @param string $collection
   *   The configuration collection.
   * @param string $op
   *   The change operation to get the unprocessed list for, either delete,
   *   create, rename, or update.
   * @param string $name
   *   The name of the configuration to process.
   *
   * @return bool
   *   TRUE if the configuration was imported as a configuration entity. FALSE
   *   otherwise.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the data is owned by an entity type, but the entity storage
   *   does not support imports.
   */
  protected function importInvokeOwner($collection, $op, $name) {
    // Renames are handled separately.
    if ($op == 'rename') {
      return $this->importInvokeRename($collection, $name);
    }
    // Validate the configuration object name before importing it.
    // Config::validateName($name);
    if ($entity_type = $this->configManager->getEntityTypeIdByName($name)) {
      $old_config = new Config($name, $this->storageComparer->getTargetStorage($collection), $this->eventDispatcher, $this->typedConfigManager);
      if ($old_data = $this->storageComparer->getTargetStorage($collection)->read($name)) {
        $old_config->initWithData($old_data);
      }

      $data = $this->storageComparer->getSourceStorage($collection)->read($name);
      $new_config = new Config($name, $this->storageComparer->getTargetStorage($collection), $this->eventDispatcher, $this->typedConfigManager);
      if ($data !== FALSE) {
        $new_config->setData($data);
      }

      $method = 'import' . ucfirst($op);
      $entity_storage = $this->configManager->getEntityTypeManager()->getStorage($entity_type);
      // Call to the configuration entity's storage to handle the configuration
      // change.
      if (!($entity_storage instanceof ImportableEntityStorageInterface)) {
        throw new EntityStorageException(sprintf('The entity storage "%s" for the "%s" entity type does not support imports', get_class($entity_storage), $entity_type));
      }
      $entity_storage->$method($name, $new_config, $old_config);
      $this->setProcessedConfiguration($collection, $op, $name);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Imports a configuration entity rename.
   *
   * @param string $collection
   *   The configuration collection.
   * @param string $rename_name
   *   The rename configuration name, as provided by
   *   \Drupal\Core\Config\StorageComparer::createRenameName().
   *
   * @return bool
   *   TRUE if the configuration was imported as a configuration entity. FALSE
   *   otherwise.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the data is owned by an entity type, but the entity storage
   *   does not support imports.
   *
   * @see \Drupal\Core\Config\ConfigImporter::createRenameName()
   */
  protected function importInvokeRename($collection, $rename_name) {
    $names = $this->storageComparer->extractRenameNames($rename_name);
    $entity_type_id = $this->configManager->getEntityTypeIdByName($names['old_name']);
    $old_config = new Config($names['old_name'], $this->storageComparer->getTargetStorage($collection), $this->eventDispatcher, $this->typedConfigManager);
    if ($old_data = $this->storageComparer->getTargetStorage($collection)->read($names['old_name'])) {
      $old_config->initWithData($old_data);
    }

    $data = $this->storageComparer->getSourceStorage($collection)->read($names['new_name']);
    $new_config = new Config($names['new_name'], $this->storageComparer->getTargetStorage($collection), $this->eventDispatcher, $this->typedConfigManager);
    if ($data !== FALSE) {
      $new_config->setData($data);
    }

    $entity_storage = $this->configManager->getEntityTypeManager()->getStorage($entity_type_id);
    // Call to the configuration entity's storage to handle the configuration
    // change.
    if (!($entity_storage instanceof ImportableEntityStorageInterface)) {
      throw new EntityStorageException(sprintf("The entity storage '%s' for the '%s' entity type does not support imports", get_class($entity_storage), $entity_type_id));
    }
    $entity_storage->importRename($names['old_name'], $new_config, $old_config);
    $this->setProcessedConfiguration($collection, 'rename', $rename_name);
    return TRUE;
  }

  /**
   * Determines if an import is already running.
   *
   * @return bool
   *   TRUE if an import is already running, FALSE if not.
   */
  public function alreadyImporting() {
    return !$this->lock->lockMayBeAvailable(static::LOCK_NAME);
  }

  /**
   * Gets all the service dependencies from \Drupal.
   *
   * Since the ConfigImporter handles module installation the kernel and the
   * container can be rebuilt and altered during processing. It is necessary to
   * keep the services used by the importer in sync.
   */
  protected function reInjectMe() {
    // When rebuilding the container,
    // \Drupal\Core\DrupalKernel::initializeContainer() saves the hashes of the
    // old container and passes them to the new one. So __sleep() will
    // recognize the old services and then __wakeup() will restore them from
    // the new container.
    $this->__sleep();
    $this->__wakeup();
  }

}

<?php

use Drupal\Core\Config\Config;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Installs the default configuration of a given module.
 *
 * @param
 *   The name of the module we are installing.
 *
 * @todo Make this acknowledge other storage engines rather than having
 *   SQL be hardcoded.
 */
function config_install_default_config($module) {
  $module_config_dir = drupal_get_path('module', $module) . '/config';
  if (is_dir($module_config_dir)) {
    $source_storage = new FileStorage(array('directory' => $module_config_dir));
    $target_storage = new DatabaseStorage();
    $null_storage = new NullStorage();

    // Upon module installation, only new config objects need to be created.
    // config_sync_get_changes() would potentially perform a diff of hundreds or
    // even thousands of config objects that happen to be contained in the
    // active store. We leverage the NullStorage to avoid that needless
    // computation of differences.
    $config_changes = config_sync_get_changes($source_storage, $null_storage);
    if (empty($config_changes)) {
      return;
    }
    $remaining_changes = config_import_invoke_owner($config_changes, $source_storage, $target_storage);
    config_sync_changes($remaining_changes, $source_storage, $target_storage);
  }
}

/**
 * @todo Modules need a way to access the active store, whatever it is.
 */
function config_get_storage_names_with_prefix($prefix = '') {
  $storage = new DatabaseStorage();
  return $storage->listAll($prefix);
}

/**
 * Retrieves a configuration object.
 *
 * This is the main entry point to the configuration API. Calling
 * @code config(book.admin) @endcode will return a configuration object in which
 * the book module can store its administrative settings.
 *
 * @param $name
 *   The name of the configuration object to retrieve. The name corresponds to
 *   a configuration file. For @code config(book.admin) @endcode, the config
 *   object returned will contain the contents of book.admin configuration file.
 *
 * @return Drupal\Core\Config\Config
 *   A configuration object.
 */
function config($name) {
  return drupal_container()->get('config.factory')->get($name)->load();
}

/**
 * Returns a list of differences between configuration storages.
 *
 * @param Drupal\Core\Config\StorageInterface $source_storage
 *   The storage to synchronize configuration from.
 * @param Drupal\Core\Config\StorageInterface $target_storage
 *   The storage to synchronize configuration to.
 *
 * @return array|bool
 *   An assocative array containing the differences between source and target
 *   storage, or FALSE if there are no differences.
 */
function config_sync_get_changes(StorageInterface $source_storage, StorageInterface $target_storage) {
  $source_names = $source_storage->listAll();
  $target_names = $target_storage->listAll();
  $config_changes = array(
    'create' => array_diff($source_names, $target_names),
    'change' => array(),
    'delete' => array_diff($target_names, $source_names),
  );
  foreach (array_intersect($source_names, $target_names) as $name) {
    $source_config_data = $source_storage->read($name);
    $target_config_data = $target_storage->read($name);
    if ($source_config_data != $target_config_data) {
      $config_changes['change'][] = $name;
    }
  }

  // Do not trigger subsequent synchronization operations if there are no
  // changes in either category.
  if (empty($config_changes['create']) && empty($config_changes['change']) && empty($config_changes['delete'])) {
    return FALSE;
  }
  return $config_changes;
}

/**
 * Writes an array of config file changes from a source storage to a target storage.
 *
 * @param array $config_changes
 *   An array of changes to be written.
 * @param Drupal\Core\Config\StorageInterface $source_storage
 *   The storage to synchronize configuration from.
 * @param Drupal\Core\Config\StorageInterface $target_storage
 *   The storage to synchronize configuration to.
 */
function config_sync_changes(array $config_changes, StorageInterface $source_storage, StorageInterface $target_storage) {
  foreach (array('delete', 'create', 'change') as $op) {
    foreach ($config_changes[$op] as $name) {
      if ($op == 'delete') {
        $target_storage->delete($name);
      }
      else {
        $data = $source_storage->read($name);
        $target_storage->write($name, $data);
      }
    }
  }
}

/**
 * Invokes MODULE_config_import() callbacks for configuration changes.
 *
 * @param array $config_changes
 *   An array of changes to be loaded.
 * @param Drupal\Core\Config\StorageInterface $source_storage
 *   The storage to synchronize configuration from.
 * @param Drupal\Core\Config\StorageInterface $target_storage
 *   The storage to synchronize configuration to.
 *
 * @todo Add support for other extension types; e.g., themes etc.
 */
function config_import_invoke_owner(array $config_changes, StorageInterface $source_storage, StorageInterface $target_storage) {
  $storage_dispatcher = drupal_container()->get('config.storage.dispatcher');

  // Allow modules to take over configuration change operations for
  // higher-level configuration data.
  // First pass deleted, then new, and lastly changed configuration, in order to
  // handle dependencies correctly.
  foreach (array('delete', 'create', 'change') as $op) {
    foreach ($config_changes[$op] as $key => $name) {
      // Extract owner from configuration object name.
      $module = strtok($name, '.');
      // Check whether the module implements hook_config_import() and ask it to
      // handle the configuration change.
      $handled_by_module = FALSE;
      if (module_hook($module, 'config_import_' . $op)) {
        $old_config = new Config($storage_dispatcher);
        $old_config
          ->setName($name)
          ->load();

        $data = $source_storage->read($name);
        $new_config = new Config($storage_dispatcher);
        $new_config->setName($name);
        if ($data !== FALSE) {
          $new_config->setData($data);
        }

        $handled_by_module = module_invoke($module, 'config_import_' . $op, $name, $new_config, $old_config);
      }
      if (!empty($handled_by_module)) {
        unset($config_changes[$op][$key]);
      }
    }
  }
  return $config_changes;
}

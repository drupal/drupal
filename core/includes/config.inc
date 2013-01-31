<?php

use Drupal\Core\Config\Config;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Config import lock name used to prevent concurrent synchronizations.
 */
const CONFIG_IMPORT_LOCK = 'config_import';

/**
 * Installs the default configuration of a given extension.
 *
 * @param string $type
 *   The extension type; e.g., 'module' or 'theme'.
 * @param string $name
 *   The name of the module or theme to install default configuration for.
 */
function config_install_default_config($type, $name) {
  $config_dir = drupal_get_path($type, $name) . '/config';
  if (is_dir($config_dir)) {
    $source_storage = new FileStorage($config_dir);
    $target_storage = drupal_container()->get('config.storage');

    // If this module defines any ConfigEntity types, then create a manifest file
    // for each of them with a listing of the objects it maintains.
    foreach (config_get_module_config_entities($name) as $entity_type => $entity_info) {
      $manifest_config = config('manifest.' . $entity_info['config_prefix']);
      $manifest_data = array();
      foreach ($source_storage->listAll($entity_info['config_prefix']) as $config_name) {
        list(, , $id) = explode('.', $config_name);
        $manifest_data[$id]['name'] = $config_name;
      }
      $manifest_config->setData($manifest_data)->save();
    }

    $config_changes = array(
      'delete' => array(),
      'create' => array(),
      'change' => array(),
    );
    $config_changes['create'] = $source_storage->listAll();
    if (empty($config_changes['create'])) {
      return;
    }
    $remaining_changes = config_import_invoke_owner($config_changes, $source_storage, $target_storage);
    config_sync_changes($remaining_changes, $source_storage, $target_storage);
  }
}

/**
 * Uninstalls the default configuration of a given extension.
 *
 * @param string $type
 *   The extension type; e.g., 'module' or 'theme'.
 * @param string $name
 *   The name of the module or theme to install default configuration for.
 */
function config_uninstall_default_config($type, $name) {
  $storage = drupal_container()->get('config.storage');
  $config_names = $storage->listAll($name . '.');
  foreach ($config_names as $config_name) {
    config($config_name)->delete();
  }

  // If this module defines any ConfigEntity types, then delete the manifest
  // file for each of them.
  foreach (config_get_module_config_entities($name) as $entity_type) {
    config('manifest.' . $entity_info['config_prefix'])->delete();
  }
}

/**
 * Gets configuration object names starting with a given prefix.
 *
 * @see Drupal\Core\Config\StorageInterface::listAll()
 */
function config_get_storage_names_with_prefix($prefix = '') {
  return drupal_container()->get('config.storage')->listAll($prefix);
}

/**
 * Retrieves a configuration object.
 *
 * This is the main entry point to the configuration API. Calling
 * @code config('book.admin') @endcode will return a configuration object in
 * which the book module can store its administrative settings.
 *
 * @param $name
 *   The name of the configuration object to retrieve. The name corresponds to
 *   a configuration file. For @code config('book.admin') @endcode, the config
 *   object returned will contain the contents of book.admin configuration file.
 *
 * @return Drupal\Core\Config\Config
 *   A configuration object.
 */
function config($name) {
  return drupal_container()->get('config.factory')->get($name);
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
  // Config entities maintain 'manifest' files that list the objects they
  // are currently handling. Each file is a simple indexed array of config
  // object names. In order to generate a list of objects that have been
  // created or deleted we need to open these files in both the source and
  // target storage, generate an array of the objects, and compare them.
  $source_config_data = array();
  $target_config_data = array();
  foreach ($source_storage->listAll('manifest') as $name) {
    if ($source_manifest_data = $source_storage->read($name)) {
      $source_config_data = array_merge($source_config_data, $source_manifest_data);
    }

    if ($target_manifest_data = $target_storage->read($name)) {
      $target_config_data = array_merge($target_config_data, $target_manifest_data);
    }
  }

  $config_changes = array(
    'create' => array(),
    'change' => array(),
    'delete' => array(),
  );

  foreach (array_diff_key($target_config_data, $source_config_data) as $name => $value) {
    $config_changes['delete'][] = $value['name'];
  }

  foreach (array_diff_key($source_config_data, $target_config_data) as $name => $value) {
    $config_changes['create'][] = $value['name'];
  }

  foreach (array_intersect($source_storage->listAll(), $target_storage->listAll()) as $name) {
    // Ignore manifest files
    if (substr($name, 0, 9) != 'manifest.') {
      $source_config_data = $source_storage->read($name);
      $target_config_data = $target_storage->read($name);
      if ($source_config_data !== $target_config_data) {
        $config_changes['change'][] = $name;
      }
    }
  }

  // Do not trigger subsequent synchronization operations if there are no
  // changes in any category.
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
  $factory = drupal_container()->get('config.factory');
  foreach (array('delete', 'create', 'change') as $op) {
    foreach ($config_changes[$op] as $name) {
      $config = new Config($name, $target_storage);
      if ($op == 'delete') {
        $config->delete();
      }
      else {
        $data = $source_storage->read($name);
        $config->setData($data ? $data : array());
        $config->save();
      }
      $factory->reset($name);
    }
  }
}

/**
 * Imports configuration into the active configuration.
 *
 * @return bool|null
 *   TRUE if configuration was imported successfully, FALSE in case of a
 *   synchronization error, or NULL if there are no changes to synchronize.
 */
function config_import() {
  // Retrieve a list of differences between staging and the active configuration.
  $source_storage = drupal_container()->get('config.storage.staging');
  $target_storage = drupal_container()->get('config.storage');

  $config_changes = config_sync_get_changes($source_storage, $target_storage);
  if (empty($config_changes)) {
    return;
  }

  if (!lock()->acquire(CONFIG_IMPORT_LOCK)) {
    // Another request is synchronizing configuration.
    // Return a negative result for UI purposes. We do not differentiate between
    // an actual synchronization error and a failed lock, because concurrent
    // synchronizations are an edge-case happening only when multiple developers
    // or site builders attempt to do it without coordinating.
    return FALSE;
  }

  $success = TRUE;
  try {
    $remaining_changes = config_import_invoke_owner($config_changes, $source_storage, $target_storage);
    config_sync_changes($remaining_changes, $source_storage, $target_storage);
  }
  catch (ConfigException $e) {
    watchdog_exception('config_import', $e);
    $success = FALSE;
  }
  lock()->release(CONFIG_IMPORT_LOCK);

  return $success;
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
  // Allow modules to take over configuration change operations for
  // higher-level configuration data.
  // First pass deleted, then new, and lastly changed configuration, in order to
  // handle dependencies correctly.
  $manager = drupal_container()->get('plugin.manager.entity');
  foreach (array('delete', 'create', 'change') as $op) {
    foreach ($config_changes[$op] as $key => $name) {
      // Call to the configuration entity's storage controller to handle the
      // configuration change.
      $handled_by_module = FALSE;
      // Validate the configuration object name before importing it.
      Config::validateName($name);
      if ($entity_type = config_get_entity_type_by_name($name)) {
        $old_config = new Config($name, $target_storage);
        $old_config->load();

        $data = $source_storage->read($name);
        $new_config = new Config($name, $target_storage);
        if ($data !== FALSE) {
          $new_config->setData($data);
        }

        $method = 'import' . ucfirst($op);
        $handled_by_module = $manager->getStorageController($entity_type)->$method($name, $new_config, $old_config);
      }
      if (!empty($handled_by_module)) {
        unset($config_changes[$op][$key]);
      }
    }
  }
  return $config_changes;
}

/**
 * Return a list of all config entity types provided by a module.
 *
 * @param string $module
 *   The name of the module possibly providing config entities.
 *
 * @return array
 *   An associative array containing the entity info for any config entities
 *   provided by the requested module, keyed by the entity type.
 */
function config_get_module_config_entities($module) {
  // While this is a lot of work to generate, it's not worth static caching
  // since this function is only called at install/uninstall, and only
  // once per module.
  $info = entity_get_info();
  return array_filter($info, function($entity_info) use ($module) {
    return ($entity_info['module'] == $module) && is_subclass_of($entity_info['class'], 'Drupal\Core\Config\Entity\ConfigEntityInterface');
  });
}

/**
 * Returns the entity type of a configuration object.
 *
 * @param string $name
 *   The configuration object name.
 *
 * @return string|null
 *   Either the entity type name, or NULL if none match.
 */
function config_get_entity_type_by_name($name) {
  $entities = array_filter(entity_get_info(), function($entity_info) use ($name) {
    return (isset($entity_info['config_prefix']) && strpos($name, $entity_info['config_prefix'] . '.') === 0);
  });
  return key($entities);
}

/*
 * Returns the typed config manager service.
 *
 * Use the typed data manager service for creating typed configuration objects.
 *
 * @see Drupal\Core\TypedData\TypedDataManager::create()
 *
 * @return Drupal\Core\TypedData\TypedConfigManager
 */
function config_typed() {
  return drupal_container()->get('config.typed');
}

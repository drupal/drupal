<?php

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\Context\FreeConfigContext;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Symfony\Component\Yaml\Dumper;

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Installs the default configuration of a given extension.
 *
 * When an extension is installed, it searches all the default configuration
 * directories for all other extensions to locate any configuration with its
 * name prefix. For example, the Node module provides the frontpage view as a
 * default configuration file:
 * core/modules/node/config/views.view.frontpage.yml
 * When the Views module is installed after the Node module is already enabled,
 * the frontpage view will be installed.
 *
 * Additionally, the default configuration directory for the extension being
 * installed is searched to discover if it contains default configuration
 * that is owned by other enabled extensions. So, the frontpage view will also
 * be installed when the Node module is installed after Views.
 *
 * @param string $type
 *   The extension type; e.g., 'module' or 'theme'.
 * @param string $name
 *   The name of the module or theme to install default configuration for.
 *
 * @see \Drupal\Core\Config\ExtensionInstallStorage
 */
function config_install_default_config($type, $name) {
  // Get all default configuration owned by this extension.
  $source_storage = new ExtensionInstallStorage();
  $config_to_install = $source_storage->listAll($name . '.');

  // Work out if this extension provides default configuration for any other
  // enabled extensions.
  $config_dir = drupal_get_path($type, $name) . '/config';
  if (is_dir($config_dir)) {
    $default_storage = new FileStorage($config_dir);
    $other_module_config = array_filter($default_storage->listAll(),
      function ($value) use ($name) {
        return !preg_match('/^' . $name . '\./', $value);
      }
    );
    $enabled_extensions = array_keys(\Drupal::moduleHandler()->getModuleList());
    $enabled_extensions += array_keys(array_filter(list_themes(), function ($theme) {return $theme->status;}));

    $other_module_config = array_filter($other_module_config, function ($config_name) use ($enabled_extensions) {
      $provider = Unicode::substr($config_name, 0, strpos($config_name, '.'));
      return in_array($provider, $enabled_extensions);
    });

    $config_to_install = array_merge($config_to_install, $other_module_config);
  }
  if (!empty($config_to_install)) {
    $entity_manager = Drupal::service('entity.manager');
    $config_factory = Drupal::service('config.factory');
    $context = new FreeConfigContext(Drupal::service('event_dispatcher'), Drupal::service('uuid'));
    $target_storage = Drupal::service('config.storage');
    $config_factory->enterContext($context);
    foreach ($config_to_install as $name) {
      // Only import new config.
      if ($target_storage->exists($name)) {
        continue;
      }

      $new_config = new Config($name, $target_storage, $context);
      $data = $source_storage->read($name);
      if ($data !== FALSE) {
        $new_config->setData($data);
      }
      if ($entity_type = config_get_entity_type_by_name($name)) {
        $entity_manager
          ->getStorageController($entity_type)
          ->create($new_config->get())
          ->save();
      }
      else {
        $new_config->save();
      }

      // Reset static cache on the config factory.
      $config_factory->reset($name);
    }
    $config_factory->leaveContext();
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
    \Drupal::config($config_name)->delete();
  }
}

/**
 * Gets configuration object names starting with a given prefix.
 *
 * @see \Drupal\Core\Config\StorageInterface::listAll()
 */
function config_get_storage_names_with_prefix($prefix = '') {
  return drupal_container()->get('config.storage')->listAll($prefix);
}

/**
 * Retrieves a configuration object.
 *
 * This is the main entry point to the configuration API. Calling
 * @code \Drupal::config('book.admin') @endcode will return a configuration
 * object in which the book module can store its administrative settings.
 *
 * @deprecated as of Drupal 8.0. Use \Drupal::config() instead.
 *
 * @param string $name
 *   The name of the configuration object to retrieve. The name corresponds to
 *   a configuration file. For @code \Drupal::config('book.admin') @endcode, the
 *   config object returned will contain the contents of book.admin
 *   configuration file.
 *
 * @return \Drupal\Core\Config\Config
 *   A configuration object.
 */
function config($name) {
  return \Drupal::config($name);
}

/**
 * Sets the config context on the config factory.
 *
 * This allows configuration objects to be created using special configuration
 * contexts eg. global override free or locale using a user preferred language.
 * Calling this function affects all subsequent calls to \Drupal::config() until
 * config_context_leave() is called.
 *
 * @see config_context_leave()
 * @see \Drupal\Core\Config\ConfigFactory
 *
 * @param string $context_name
 *   The name of the config context service on the container or a fully
 *   qualified class implementing \Drupal\Core\Config\Context\ContextInterface.
 *
 * @return \Drupal\Core\Config\Context\ContextInterface
 *   The configuration context object.
 */
function config_context_enter($context_name) {
  if (drupal_container()->has($context_name)) {
    $context = drupal_container()->get($context_name);
  }
  elseif (class_exists($context_name) && in_array('Drupal\Core\Config\Context\ContextInterface', class_implements($context_name))) {
    $context = drupal_container()
      ->get('config.context.factory')
      ->get($context_name);
  }
  else {
    throw new ConfigException(sprintf('Unknown config context service or class: %s', $context_name));
  }
  drupal_container()
    ->get('config.factory')
    ->enterContext($context);
  return $context;
}

/**
 * Leaves the current config context returning to the previous context.
 *
 * @see config_context_enter()
 * @see \Drupal\Core\Config\ConfigFactory
 */
function config_context_leave() {
  drupal_container()
    ->get('config.factory')
    ->leaveContext();
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

/**
 * Returns the typed config manager service.
 *
 * Use the typed data manager service for creating typed configuration objects.
 *
 * @see \Drupal\Core\TypedData\TypedDataManager::create()
 *
 * @return \Drupal\Core\TypedData\TypedConfigManager
 */
function config_typed() {
  return drupal_container()->get('config.typed');
}

/**
 * Creates a configuration snapshot following a successful import.
 *
 * @param \Drupal\Core\Config\StorageInterface $source_storage
 *   The storage to synchronize configuration from.
 * @param \Drupal\Core\Config\StorageInterface $snapshot_storage
 *   The storage to synchronize configuration to.
 */
function config_import_create_snapshot(StorageInterface $source_storage, StorageInterface $snapshot_storage) {
  $snapshot_storage->deleteAll();
  foreach ($source_storage->listAll() as $name) {
    $snapshot_storage->write($name, $source_storage->read($name));
  }
}

/**
 * Return a formatted diff of a named config between two storages.
 *
 * @param \Drupal\Core\Config\StorageInterface $source_storage
 *   The storage to diff configuration from.
 * @param \Drupal\Core\Config\StorageInterface $target_storage
 *   The storage to diff configuration to.
 * @param string $name
 *   The name of the configuration object to diff.
 *
 * @return core/lib/Drupal/Component/Diff
 *   A formatted string showing the difference between the two storages.
 *
 * @todo Make renderer injectable
 */
function config_diff(StorageInterface $source_storage, StorageInterface $target_storage, $name) {
  require_once DRUPAL_ROOT . '/core/lib/Drupal/Component/Diff/DiffEngine.php';

  // The output should show configuration object differences formatted as YAML.
  // But the configuration is not necessarily stored in files. Therefore, they
  // need to be read and parsed, and lastly, dumped into YAML strings.
  $dumper = new Dumper();
  $dumper->setIndentation(2);

  $source_data = explode("\n", $dumper->dump($source_storage->read($name), PHP_INT_MAX));
  $target_data = explode("\n", $dumper->dump($target_storage->read($name), PHP_INT_MAX));

  // Check for new or removed files.
  if ($source_data === array('false')) {
    // Added file.
    $source_data = array(t('File added'));
  }
  if ($target_data === array('false')) {
    // Deleted file.
    $target_data = array(t('File removed'));
  }

  return new Diff($source_data, $target_data);
}

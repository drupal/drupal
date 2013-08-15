<?php

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\ConfigInstaller;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageComparer;
use Symfony\Component\Yaml\Dumper;

/**
 * @file
 * This is the API for configuration storage.
 */

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
    $storage_comparer = new StorageComparer($source_storage, Drupal::service('config.storage'));
    // Only import new config. Changed config is from previous enables and
    // should not be overwritten.
    $storage_comparer->addChangelistCreate();
    $installer = new ConfigInstaller(
      $storage_comparer,
      Drupal::service('event_dispatcher'),
      Drupal::service('config.factory'),
      Drupal::entityManager(),
      Drupal::lock()
    );
    $installer->import();
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
    Drupal::config($config_name)->delete();
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
 * @return Drupal\Core\Config\Config
 *   A configuration object.
 */
function config($name) {
  return Drupal::config($name);
}

/**
 * Sets the config context on the config factory.
 *
 * This allows configuration objects to be created using special configuration
 * contexts eg. global override free or locale using a user preferred language.
 * Calling this function affects all subsequent calls to Drupal::config() until
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

/**
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

/**
 * Creates a configuration snapshot following a successful import.
 *
 * @param Drupal\Core\Config\StorageInterface $source_storage
 *   The storage to synchronize configuration from.
 * @param Drupal\Core\Config\StorageInterface $snapshot_storage
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
 * @param Drupal\Core\Config\StorageInterface $source_storage
 *   The storage to diff configuration from.
 * @param Drupal\Core\Config\StorageInterface $target_storage
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

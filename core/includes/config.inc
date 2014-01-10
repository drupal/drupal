<?php

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Language\Language;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @file
 * This is the API for configuration storage.
 */

/**
 * Uninstalls the default configuration of a given extension.
 *
 * @param string $type
 *   The extension type; e.g., 'module' or 'theme'.
 * @param string $name
 *   The name of the module or theme to install default configuration for.
 */
function config_uninstall_default_config($type, $name) {
  $storage = \Drupal::service('config.storage');
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
  return \Drupal::service('config.storage')->listAll($prefix);
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
 * Returns the entity type of a configuration object.
 *
 * @param string $name
 *   The configuration object name.
 *
 * @return string|null
 *   Either the entity type name, or NULL if none match.
 */
function config_get_entity_type_by_name($name) {
  $entities = array_filter(\Drupal::entityManager()->getDefinitions(), function (EntityTypeInterface $entity_info) use ($name) {
    return ($config_prefix = $entity_info->getConfigPrefix()) && strpos($name, $config_prefix . '.') === 0;
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
 * @return \Drupal\Core\Config\TypedConfigManager
 */
function config_typed() {
  return \Drupal::service('config.typed');
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

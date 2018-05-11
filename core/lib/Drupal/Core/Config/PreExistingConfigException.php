<?php

namespace Drupal\Core\Config;

use Drupal\Component\Render\FormattableMarkup;

/**
 * An exception thrown if configuration with the same name already exists.
 */
class PreExistingConfigException extends ConfigException {

  /**
   * A list of configuration objects that already exist in active configuration.
   *
   * @var array
   */
  protected $configObjects = [];

  /**
   * The name of the module that is being installed.
   *
   * @var string
   */
  protected $extension;

  /**
   * Gets the list of configuration objects that already exist.
   *
   * @return array
   *   A list of configuration objects that already exist in active
   *   configuration keyed by collection.
   */
  public function getConfigObjects() {
    return $this->configObjects;
  }

  /**
   * Gets the name of the extension that is being installed.
   *
   * @return string
   *   The name of the extension that is being installed.
   */
  public function getExtension() {
    return $this->extension;
  }

  /**
   * Creates an exception for an extension and a list of configuration objects.
   *
   * @param $extension
   *   The name of the extension that is being installed.
   * @param array $config_objects
   *   A list of configuration objects that already exist in active
   *   configuration, keyed by config collection.
   *
   * @return \Drupal\Core\Config\PreExistingConfigException
   */
  public static function create($extension, array $config_objects) {
    $message = new FormattableMarkup('Configuration objects (@config_names) provided by @extension already exist in active configuration',
      [
        '@config_names' => implode(', ', static::flattenConfigObjects($config_objects)),
        '@extension' => $extension,
      ]
    );
    $e = new static($message);
    $e->configObjects = $config_objects;
    $e->extension = $extension;
    return $e;
  }

  /**
   * Flattens the config object array to a single dimensional list.
   *
   * @param array $config_objects
   *   A list of configuration objects that already exist in active
   *   configuration, keyed by config collection.
   *
   * @return array
   *   A list of configuration objects that have been prefixed with their
   *   collection.
   */
  public static function flattenConfigObjects(array $config_objects) {
    $flat_config_objects = [];
    foreach ($config_objects as $collection => $config_names) {
      $config_names = array_map(function ($config_name) use ($collection) {
        if ($collection != StorageInterface::DEFAULT_COLLECTION) {
          $config_name = str_replace('.', DIRECTORY_SEPARATOR, $collection) . DIRECTORY_SEPARATOR . $config_name;
        }
        return $config_name;
      }, $config_names);
      $flat_config_objects = array_merge($flat_config_objects, $config_names);
    }
    return $flat_config_objects;
  }

}

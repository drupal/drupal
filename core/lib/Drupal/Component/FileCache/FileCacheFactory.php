<?php

namespace Drupal\Component\FileCache;

/**
 * Creates a FileCache object.
 */
class FileCacheFactory {

  /**
   * The configuration key to disable FileCache completely.
   */
  const DISABLE_CACHE = 'file_cache_disable';

  /**
   * The configuration used to create FileCache objects.
   *
   * @var array
   */
  protected static $configuration;

  /**
   * The cache prefix.
   *
   * @var string
   */
  protected static $prefix;

  /**
   * Instantiates a FileCache object for a given collection identifier.
   *
   * @param string $collection
   *   The collection identifier for this FileCache.
   * @param array $default_configuration
   *   (optional) The default configuration for this FileCache collection. This
   *   can be used to e.g. specify default usage of a FileCache class.
   *
   * @return \Drupal\Component\FileCache\FileCacheInterface
   *   The initialized FileCache object.
   */
  public static function get($collection, $default_configuration = []) {
    // If there is a special key in the configuration, disable FileCache completely.
    if (!empty(static::$configuration[static::DISABLE_CACHE])) {
      return new NullFileCache('', '');
    }

    $configuration = [];

    // Check for a collection specific setting first.
    if (isset(static::$configuration[$collection])) {
      $configuration += static::$configuration[$collection];
    }
    // Then check if a default configuration has been provided.
    if (!empty($default_configuration)) {
      $configuration += $default_configuration;
    }
    // Last check if a default setting has been provided.
    if (isset(static::$configuration['default'])) {
      $configuration += static::$configuration['default'];
    }

    // Ensure that all properties are set.
    $fallback_configuration = [
      'class' => '\Drupal\Component\FileCache\FileCache',
      'collection' => $collection,
      'cache_backend_class' => NULL,
      'cache_backend_configuration' => [],
    ];

    $configuration = $configuration + $fallback_configuration;

    $class = $configuration['class'];
    return new $class(static::getPrefix(), $configuration['collection'], $configuration['cache_backend_class'], $configuration['cache_backend_configuration']);
  }

  /**
   * Gets the configuration used for constructing future file cache objects.
   *
   * @return array
   *   The configuration that is used.
   */
  public static function getConfiguration() {
    return static::$configuration;
  }

  /**
   * Sets the configuration to use for constructing future file cache objects.
   *
   * @param array $configuration
   *   The configuration to use.
   */
  public static function setConfiguration($configuration) {
    static::$configuration = $configuration;
  }

  /**
   * Returns the cache prefix.
   *
   * @return string
   *   The cache prefix.
   */
  public static function getPrefix() {
    return static::$prefix;
  }

  /**
   * Sets the cache prefix that should be used.
   *
   * Should be set to a secure, unique key to prevent cache pollution by a
   * third party.
   *
   * @param string $prefix
   *   The cache prefix.
   */
  public static function setPrefix($prefix) {
    static::$prefix = $prefix;
  }

}

<?php

namespace Drupal\Core\PhpStorage;

use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Creates a php storage object.
 */
class PhpStorageFactory {

  /**
   * Instantiates a storage for generated PHP code.
   *
   * By default, this returns an instance of the
   * \Drupal\Component\PhpStorage\MTimeProtectedFileStorage class.
   *
   * Classes implementing
   * \Drupal\Component\PhpStorage\PhpStorageInterface can be registered for a
   * specific bin or as a default implementation.
   *
   * @param string $name
   *   The name for which the storage should be returned. Defaults to 'default'
   *   The name is also used as the storage bin if one is not specified in the
   *   configuration.
   *
   * @return \Drupal\Component\PhpStorage\PhpStorageInterface
   *   An instantiated storage for the specified name.
   */
  public static function get($name) {
    $configuration = [];
    $overrides = Settings::get('php_storage');
    if (isset($overrides[$name])) {
      $configuration = $overrides[$name];
    }
    elseif (isset($overrides['default'])) {
      $configuration = $overrides['default'];
    }
    // Make sure all the necessary configuration values are set.
    $class = $configuration['class'] ?? 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage';
    if (!isset($configuration['secret'])) {
      $configuration['secret'] = Settings::getHashSalt();
    }
    if (!isset($configuration['bin'])) {
      $configuration['bin'] = $name;
    }
    if (!isset($configuration['directory'])) {
      $configuration['directory'] = PublicStream::basePath() . '/php';
    }
    return new $class($configuration);
  }

}

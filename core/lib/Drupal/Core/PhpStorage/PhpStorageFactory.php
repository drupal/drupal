<?php

/**
 * @file
 * Definition of Drupal\Component\PhpStorage\PhpStorageFactory.
 */

namespace Drupal\Core\PhpStorage;

use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Creates a php storage object
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
  static function get($name) {
    $overrides = Settings::get('php_storage');
    if (isset($overrides[$name])) {
      $configuration = $overrides[$name];
    }
    elseif (isset($overrides['default'])) {
      $configuration = $overrides['default'];
    }
    else {
      $configuration = array(
        'class' => 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage',
        'secret' => drupal_get_hash_salt(),
      );
    }
    $class = isset($configuration['class']) ? $configuration['class'] : 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage';
    if (!isset($configuration['bin'])) {
      $configuration['bin'] = $name;
    }
    if (!isset($configuration['directory'])) {
      $configuration['directory'] = DRUPAL_ROOT . '/' . PublicStream::basePath() . '/php';
    }
    return new $class($configuration);
  }

}

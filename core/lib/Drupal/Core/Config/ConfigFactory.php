<?php

/**
 * @file
 * Definition of Drupal\Core\Config\ConfigFactory.
 */

namespace Drupal\Core\Config;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Defines the configuration object factory.
 *
 * The configuration object factory instantiates a Config object for each
 * configuration object name that is accessed and returns it to callers.
 *
 * @see Drupal\Core\Config\Config
 *
 * Each configuration object gets a storage controller object injected, which
 * is used for reading and writing the configuration data.
 *
 * @see Drupal\Core\Config\StorageInterface
 */
class ConfigFactory {

  /**
   * A storage controller instance for reading and writing configuration data.
   *
   * @var Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Cached configuration objects.
   *
   * @var array
   */
  protected $cache = array();

  /**
   * Constructs the Config factory.
   *
   * @param Drupal\Core\Config\StorageInterface $storage
   *   The storage controller object to use for reading and writing
   *   configuration data.
   * @param Symfony\Component\EventDispatcher\EventDispatcher
   *   An event dispatcher instance to use for configuration events.
   */
  public function __construct(StorageInterface $storage, EventDispatcher $event_dispatcher) {
    $this->storage = $storage;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Returns a configuration object for a given name.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   *
   * @return Drupal\Core\Config\Config
   *   A configuration object with the given $name.
   */
  public function get($name) {
    global $conf;

    if (isset($this->cache[$name])) {
      return $this->cache[$name];
    }

    $this->cache[$name] = new Config($name, $this->storage, $this->eventDispatcher);
    return $this->cache[$name]->init();
  }

  /**
   * Resets and re-initializes configuration objects. Internal use only.
   *
   * @param string $name
   *   (optional) The name of the configuration object to reset. If omitted, all
   *   configuration objects are reset.
   */
  public function reset($name = NULL) {
    if ($name) {
      if (isset($this->cache[$name])) {
        $this->cache[$name]->init();
      }
    }
    else {
      foreach ($this->cache as $config) {
        $config->init();
      }
    }
  }

  /**
   * Renames a configuration object in the cache.
   *
   * @param string $old_name
   *   The old name of the configuration object.
   * @param string $new_name
   *   The new name of the configuration object.
   *
   * @todo D8: Remove after http://drupal.org/node/1865206.
   */
  public function rename($old_name, $new_name) {
    if (isset($this->cache[$old_name])) {
      $config = $this->cache[$old_name];
      // Clone the object into the existing slot.
      $this->cache[$old_name] = clone $config;

      // Change the object's name and re-initialize it.
      $config->setName($new_name)->init();
      $this->cache[$new_name] = $config;
    }
  }
}

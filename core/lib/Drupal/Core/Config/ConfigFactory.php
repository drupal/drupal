<?php

/**
 * @file
 * Definition of Drupal\Core\Config\ConfigFactory.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Config\Context\ContextInterface;

/**
 * Defines the configuration object factory.
 *
 * The configuration object factory instantiates a Config object for each
 * configuration object name that is accessed and returns it to callers.
 *
 * @see \Drupal\Core\Config\Config
 *
 * Each configuration object gets a storage controller object injected, which
 * is used for reading and writing the configuration data.
 *
 * @see \Drupal\Core\Config\StorageInterface
 *
 * A configuration context is an object containing parameters that will be
 * available to the configuration plug-ins for them to customize the
 * configuration data in different ways.
 *
 * @see \Drupal\Core\Config\Context\ContextInterface
 */
class ConfigFactory {

  /**
   * A storage controller instance for reading and writing configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * A stack of configuration contexts the last being the context in use.
   *
   * @var array
   */
  protected $contextStack = array();

  /**
   * Cached configuration objects.
   *
   * @var array
   */
  protected $cache = array();

  /**
   * Constructs the Config factory.
   *
   * @param \Drupal\Core\Config\StorageInterface
   *   The configuration storage engine.
   * @param \Drupal\Core\Config\Context\ContextInterface
   *   Configuration context object.
   */
  public function __construct(StorageInterface $storage, ContextInterface $context) {
    $this->storage = $storage;
    $this->enterContext($context);
  }

  /**
   * Returns a configuration object for a given name and context.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  public function get($name) {
    $context = $this->getContext();
    $cache_key = $this->getCacheKey($name, $context);
    if (isset($this->cache[$cache_key])) {
      return $this->cache[$cache_key];
    }

    $this->cache[$cache_key] = new Config($name, $this->storage, $context);
    return $this->cache[$cache_key]->init();
  }

  /**
   * Returns a list of configuration objects for a given names and context.
   *
   * This will pre-load all requested configuration objects does not create
   * new configuration objects.
   *
   * @param array $names
   *   List of names of configuration objects.
   *
   * @return array
   *   List of successfully loaded configuration objects, keyed by name.
   */
  public function loadMultiple(array $names) {
    $context = $this->getContext();

    $list = array();
    foreach ($names as $key => $name) {
      $cache_key = $this->getCacheKey($name, $context);
      // @todo: Deleted configuration stays in $this->cache, only return
      //   config entities that are not new.
      if (isset($this->cache[$cache_key]) && !$this->cache[$cache_key]->isNew()) {
        $list[$name] = $this->cache[$cache_key];
        unset($names[$key]);
      }
    }

    // Pre-load remaining configuration files.
    if (!empty($names)) {
      $storage_data = $this->storage->readMultiple($names);
      foreach ($storage_data as $name => $data) {
        $cache_key = $this->getCacheKey($name, $context);
        $this->cache[$cache_key] = new Config($name, $this->storage, $context);
        $this->cache[$cache_key]->initWithData($data);
        $list[$name] = $this->cache[$cache_key];
      }
    }
    return $list;
  }

  /**
   * Resets and re-initializes configuration objects. Internal use only.
   *
   * @param string $name
   *   (optional) The name of the configuration object to reset. If omitted, all
   *   configuration objects are reset.
   *
   * @return \Drupal\Core\Config\ConfigFactory
   *   The config factory object.
   */
  public function reset($name = NULL) {
    if ($name) {
      // Clear the cached configuration object in all contexts.
      foreach ($this->getCacheKeys($name) as $cache_key) {
        unset($this->cache[$cache_key]);
      }
    }
    else {
      $this->cache = array();
    }

    // Clear the static list cache if supported by the storage.
    if ($this->storage instanceof StorageCacheInterface) {
      $this->storage->resetListCache();
    }
    return $this;
  }

  /**
   * Renames a configuration object using the storage controller.
   *
   * @param string $old_name
   *   The old name of the configuration object.
   * @param string $new_name
   *   The new name of the configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The renamed config object.
   */
  public function rename($old_name, $new_name) {
    $context = $this->getContext();
    $old_cache_key = $this->getCacheKey($old_name, $context);
    $new_cache_key = $this->getCacheKey($new_name, $context);
    if (isset($this->cache[$old_cache_key])) {
      $config = $this->cache[$old_cache_key];
      unset($this->cache[$old_cache_key]);
    }
    else {
      // Create the config object if it's not yet loaded into the static cache.
      $config = new Config($old_name, $this->storage, $context);
    }

    $this->cache[$new_cache_key] = $config;
    $this->storage->rename($old_name, $new_name);
    return $this->cache[$new_cache_key]->setName($new_name)->init();
  }

  /**
   * Sets the config context by adding it to the context stack.
   *
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context to add.
   *
   * @return \Drupal\Core\Config\ConfigFactory
   *   The config factory object.
   */
  public function enterContext(ContextInterface $context) {
    // Initialize the context as it is being entered.
    $this->contextStack[] = $context->init();
    return $this;
  }

  /**
   * Gets the current config context.
   *
   * @return \Drupal\Core\Config\Context\ContextInterface $context
   *   The current configuration context.
   */
  public function getContext() {
    return end($this->contextStack);
  }

  /**
   * Leaves the current context by removing it from the context stack.
   *
   * @return \Drupal\Core\Config\ConfigFactory
   *   The config factory object.
   */
  public function leaveContext() {
    // Ensure at least one context is left on the stack. We already ensured
    // there is at least one context set by taking the initial one in the
    // constructor.
    if (count($this->contextStack) > 1) {
      array_pop($this->contextStack);
    }
    return $this;
  }

  /**
   * Gets the cache key for a given config name in a particular context.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context.
   *
   * @return string
   *   The cache key.
   */
  public function getCacheKey($name, ContextInterface $context) {
    return $name . ':' . $context->getUuid();
  }

  /**
   * Gets all the cache keys that match the provided config name.
   *
   * @param string $name
   *   The name of the configuration object.
   *
   * @return array
   *   An array of cache keys that match the provided config name.
   */
  public function getCacheKeys($name) {
    $cache_keys = array_keys($this->cache);
    return array_filter($cache_keys, function($key) use ($name) {
      return ( strpos($key, $name) !== false );
    });
  }

  /**
   * Clears the config factory static cache.
   *
   * @return \Drupal\Core\Config\ConfigFactory
   *   The config factory object.
   */
  public function clearStaticCache() {
    $this->cache = array();
    return $this;
  }
}

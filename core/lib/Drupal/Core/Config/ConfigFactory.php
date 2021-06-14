<?php

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the configuration object factory.
 *
 * The configuration object factory instantiates a Config object for each
 * configuration object name that is accessed and returns it to callers.
 *
 * @see \Drupal\Core\Config\Config
 *
 * Each configuration object gets a storage object injected, which
 * is used for reading and writing the configuration data.
 *
 * @see \Drupal\Core\Config\StorageInterface
 *
 * @ingroup config_api
 */
class ConfigFactory implements ConfigFactoryInterface, EventSubscriberInterface {

  /**
   * A storage instance for reading and writing configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Cached configuration objects.
   *
   * @var \Drupal\Core\Config\Config[]
   */
  protected $cache = [];

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * An array of config factory override objects ordered by priority.
   *
   * @var \Drupal\Core\Config\ConfigFactoryOverrideInterface[]
   */
  protected $configFactoryOverrides = [];

  /**
   * Constructs the Config factory.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   */
  public function __construct(StorageInterface $storage, EventDispatcherInterface $event_dispatcher, TypedConfigManagerInterface $typed_config) {
    $this->storage = $storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->typedConfigManager = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditable($name) {
    return $this->doGet($name, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    return $this->doGet($name);
  }

  /**
   * Returns a configuration object for a given name.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   * @param bool $immutable
   *   (optional) Create an immutable configuration object. Defaults to TRUE.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  protected function doGet($name, $immutable = TRUE) {
    if ($config = $this->doLoadMultiple([$name], $immutable)) {
      return $config[$name];
    }
    else {
      // If the configuration object does not exist in the configuration
      // storage, create a new object.
      $config = $this->createConfigObject($name, $immutable);

      if ($immutable) {
        // Get and apply any overrides.
        $overrides = $this->loadOverrides([$name]);
        if (isset($overrides[$name])) {
          $config->setModuleOverride($overrides[$name]);
        }
        // Apply any settings.php overrides.
        if (isset($GLOBALS['config'][$name])) {
          $config->setSettingsOverride($GLOBALS['config'][$name]);
        }
      }

      foreach ($this->configFactoryOverrides as $override) {
        $config->addCacheableDependency($override->getCacheableMetadata($name));
      }

      return $config;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $names) {
    return $this->doLoadMultiple($names);
  }

  /**
   * Returns a list of configuration objects for the given names.
   *
   * @param array $names
   *   List of names of configuration objects.
   * @param bool $immutable
   *   (optional) Create an immutable configuration objects. Defaults to TRUE.
   *
   * @return \Drupal\Core\Config\Config[]|\Drupal\Core\Config\ImmutableConfig[]
   *   List of successfully loaded configuration objects, keyed by name.
   */
  protected function doLoadMultiple(array $names, $immutable = TRUE) {
    $list = [];

    foreach ($names as $key => $name) {
      $cache_key = $this->getConfigCacheKey($name, $immutable);
      if (isset($this->cache[$cache_key])) {
        $list[$name] = $this->cache[$cache_key];
        unset($names[$key]);
      }
    }

    // Pre-load remaining configuration files.
    if (!empty($names)) {
      // Initialize override information.
      $module_overrides = [];
      $storage_data = $this->storage->readMultiple($names);

      if ($immutable && !empty($storage_data)) {
        // Only get module overrides if we have configuration to override.
        $module_overrides = $this->loadOverrides($names);
      }

      foreach ($storage_data as $name => $data) {
        $cache_key = $this->getConfigCacheKey($name, $immutable);

        $this->cache[$cache_key] = $this->createConfigObject($name, $immutable);
        $this->cache[$cache_key]->initWithData($data);
        if ($immutable) {
          if (isset($module_overrides[$name])) {
            $this->cache[$cache_key]->setModuleOverride($module_overrides[$name]);
          }
          if (isset($GLOBALS['config'][$name])) {
            $this->cache[$cache_key]->setSettingsOverride($GLOBALS['config'][$name]);
          }
        }

        $this->propagateConfigOverrideCacheability($cache_key, $name);

        $list[$name] = $this->cache[$cache_key];
      }
    }

    return $list;
  }

  /**
   * Get arbitrary overrides for the named configuration objects from modules.
   *
   * @param array $names
   *   The names of the configuration objects to get overrides for.
   *
   * @return array
   *   An array of overrides keyed by the configuration object name.
   */
  protected function loadOverrides(array $names) {
    $overrides = [];
    foreach ($this->configFactoryOverrides as $override) {
      // Existing overrides take precedence since these will have been added
      // by events with a higher priority.
      $overrides = NestedArray::mergeDeepArray([$override->loadOverrides($names), $overrides], TRUE);
    }
    return $overrides;
  }

  /**
   * Propagates cacheability of config overrides to cached config objects.
   *
   * @param string $cache_key
   *   The key of the cached config object to update.
   * @param string $name
   *   The name of the configuration object to construct.
   */
  protected function propagateConfigOverrideCacheability($cache_key, $name) {
    foreach ($this->configFactoryOverrides as $override) {
      $this->cache[$cache_key]->addCacheableDependency($override->getCacheableMetadata($name));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reset($name = NULL) {
    if ($name) {
      // Clear all cached configuration for this name.
      foreach ($this->getConfigCacheKeys($name) as $cache_key) {
        unset($this->cache[$cache_key]);
      }
    }
    else {
      $this->cache = [];
    }

    // Clear the static list cache if supported by the storage.
    if ($this->storage instanceof StorageCacheInterface) {
      $this->storage->resetListCache();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($old_name, $new_name) {
    Cache::invalidateTags($this->get($old_name)->getCacheTags());
    $this->storage->rename($old_name, $new_name);

    // Clear out the static cache of any references to the old name.
    foreach ($this->getConfigCacheKeys($old_name) as $old_cache_key) {
      unset($this->cache[$old_cache_key]);
    }

    // Prime the cache and load the configuration with the correct overrides.
    $config = $this->get($new_name);
    $this->eventDispatcher->dispatch(new ConfigRenameEvent($config, $old_name), ConfigEvents::RENAME);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    // Because get() adds overrides both from $GLOBALS and from
    // $this->configFactoryOverrides, add cache keys for each.
    $keys[] = 'global_overrides';
    foreach ($this->configFactoryOverrides as $override) {
      $keys[] = $override->getCacheSuffix();
    }
    return $keys;
  }

  /**
   * Gets the static cache key for a given config name.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param bool $immutable
   *   Whether or not the object is mutable.
   *
   * @return string
   *   The cache key.
   */
  protected function getConfigCacheKey($name, $immutable) {
    $suffix = '';
    if ($immutable) {
      $suffix = ':' . implode(':', $this->getCacheKeys());
    }
    return $name . $suffix;
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
  protected function getConfigCacheKeys($name) {
    return array_filter(array_keys($this->cache), function ($key) use ($name) {
      // Return TRUE if the key is the name or starts with the configuration
      // name plus the delimiter.
      return $key === $name || strpos($key, $name . ':') === 0;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCache() {
    $this->cache = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    return $this->storage->listAll($prefix);
  }

  /**
   * Updates stale static cache entries when configuration is saved.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();

    // We are only concerned with config objects that belong to the collection
    // that matches the storage we depend on. Skip if the event was fired for a
    // config object belonging to a different collection.
    if ($saved_config->getStorage()->getCollectionName() !== $this->storage->getCollectionName()) {
      return;
    }

    // Ensure that the static cache contains up to date configuration objects by
    // replacing the data on any entries for the configuration object apart
    // from the one that references the actual config object being saved.
    foreach ($this->getConfigCacheKeys($saved_config->getName()) as $cache_key) {
      $cached_config = $this->cache[$cache_key];
      if ($cached_config !== $saved_config) {
        // We can not just update the data since other things about the object
        // might have changed. For example, whether or not it is new.
        $this->cache[$cache_key]->initWithData($saved_config->getRawData());
      }
    }
  }

  /**
   * Removes stale static cache entries when configuration is deleted.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $deleted_config = $event->getConfig();

    // We are only concerned with config objects that belong to the collection
    // that matches the storage we depend on. Skip if the event was fired for a
    // config object belonging to a different collection.
    if ($deleted_config->getStorage()->getCollectionName() !== $this->storage->getCollectionName()) {
      return;
    }

    // Ensure that the static cache does not contain deleted configuration.
    foreach ($this->getConfigCacheKeys($deleted_config->getName()) as $cache_key) {
      unset($this->cache[$cache_key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 255];
    $events[ConfigEvents::DELETE][] = ['onConfigDelete', 255];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function addOverride(ConfigFactoryOverrideInterface $config_factory_override) {
    $this->configFactoryOverrides[] = $config_factory_override;
  }

  /**
   * Creates a configuration object.
   *
   * @param string $name
   *   Configuration object name.
   * @param bool $immutable
   *   Determines whether a mutable or immutable config object is returned.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  protected function createConfigObject($name, $immutable) {
    if ($immutable) {
      return new ImmutableConfig($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
    }
    return new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
  }

}

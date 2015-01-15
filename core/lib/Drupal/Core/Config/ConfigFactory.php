<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigFactory.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * A flag indicating if we should use overrides.
   *
   * @var boolean
   */
  protected $useOverrides = TRUE;

  /**
   * Cached configuration objects.
   *
   * @var \Drupal\Core\Config\Config[]
   */
  protected $cache = array();

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
  protected $configFactoryOverrides = array();

  /**
   * Constructs the Config factory.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
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
  public function setOverrideState($state) {
    $this->useOverrides = $state;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverrideState() {
    return $this->useOverrides;
  }

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    if ($config = $this->loadMultiple(array($name))) {
      return $config[$name];
    }
    else {
      // If the configuration object does not exist in the configuration
      // storage, create a new object and add it to the static cache.
      $cache_key = $this->getConfigCacheKey($name);
      $this->cache[$cache_key] = new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);

      if ($this->useOverrides) {
        // Get and apply any overrides.
        $overrides = $this->loadOverrides(array($name));
        if (isset($overrides[$name])) {
          $this->cache[$cache_key]->setModuleOverride($overrides[$name]);
        }
        // Apply any settings.php overrides.
        if (isset($GLOBALS['config'][$name])) {
          $this->cache[$cache_key]->setSettingsOverride($GLOBALS['config'][$name]);
        }
      }
      return $this->cache[$cache_key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $names) {
    $list = array();

    foreach ($names as $key => $name) {
      $cache_key = $this->getConfigCacheKey($name);
      if (isset($this->cache[$cache_key])) {
        $list[$name] = $this->cache[$cache_key];
        unset($names[$key]);
      }
    }

    // Pre-load remaining configuration files.
    if (!empty($names)) {
      // Initialise override information.
      $module_overrides = array();
      $storage_data = $this->storage->readMultiple($names);

      if ($this->useOverrides && !empty($storage_data)) {
        // Only get module overrides if we have configuration to override.
        $module_overrides = $this->loadOverrides($names);
      }

      foreach ($storage_data as $name => $data) {
        $cache_key = $this->getConfigCacheKey($name);

        $this->cache[$cache_key] = new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
        $this->cache[$cache_key]->initWithData($data);
        if ($this->useOverrides) {
          if (isset($module_overrides[$name])) {
            $this->cache[$cache_key]->setModuleOverride($module_overrides[$name]);
          }
          if (isset($GLOBALS['config'][$name])) {
            $this->cache[$cache_key]->setSettingsOverride($GLOBALS['config'][$name]);
          }
        }
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
    $overrides = array();
    foreach ($this->configFactoryOverrides as $override) {
      // Existing overrides take precedence since these will have been added
      // by events with a higher priority.
      $overrides = NestedArray::mergeDeepArray(array($override->loadOverrides($names), $overrides), TRUE);
    }
    return $overrides;
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
      $this->cache = array();
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
    $this->eventDispatcher->dispatch(ConfigEvents::RENAME, new ConfigRenameEvent($config, $old_name));
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    $keys = array();
    if ($this->useOverrides) {
      // Because get() adds overrides both from $GLOBALS and from
      // $this->configFactoryOverrides, add cache keys for each.
      $keys[] = 'global_overrides';
      foreach($this->configFactoryOverrides as $override) {
        $keys[] =  $override->getCacheSuffix();
      }
    }
    return $keys;
  }

  /**
   * Gets the cache key for a given config name.
   *
   * @param string $name
   *   The name of the configuration object.
   *
   * @return string
   *   The cache key.
   */
  protected function getConfigCacheKey($name) {
    return $name . ':' . implode(':', $this->getCacheKeys());
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
    return array_filter(array_keys($this->cache), function($key) use ($name) {
      // Return TRUE if the key starts with the configuration name.
      return strpos($key, $name . ':') === 0;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCache() {
    $this->cache = array();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    return $this->storage->listAll($prefix);
  }

  /**
   * Removes stale static cache entries when configuration is saved.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    // Ensure that the static cache contains up to date configuration objects by
    // replacing the data on any entries for the configuration object apart
    // from the one that references the actual config object being saved.
    $saved_config = $event->getConfig();
    foreach ($this->getConfigCacheKeys($saved_config->getName()) as $cache_key) {
      $cached_config = $this->cache[$cache_key];
      if ($cached_config !== $saved_config) {
        $this->cache[$cache_key]->setData($saved_config->getRawData());
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
    // Ensure that the static cache does not contain deleted configuration.
    foreach ($this->getConfigCacheKeys($event->getConfig()->getName()) as $cache_key) {
      unset($this->cache[$cache_key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 255);
    $events[ConfigEvents::DELETE][] = array('onConfigDelete', 255);
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function addOverride(ConfigFactoryOverrideInterface $config_factory_override) {
    $this->configFactoryOverrides[] = $config_factory_override;
  }

}

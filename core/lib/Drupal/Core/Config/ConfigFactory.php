<?php

/**
 * @file
 * Definition of Drupal\Core\Config\ConfigFactory.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Language\Language;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
 */
class ConfigFactory implements EventSubscriberInterface {

  /**
   * Prefix for all language configuration files.
   */
  const LANGUAGE_CONFIG_PREFIX = 'language.config';

  /**
   * A storage controller instance for reading and writing configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * A flag indicating if we should use overrides.
   *
   * @var boolean
   */
  protected $useOverrides = TRUE;

  /**
   * The language object used to override configuration data.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $language;

  /**
   * Cached configuration objects.
   *
   * @var \Drupal\Core\Config\Config[]
   */
  protected $cache = array();

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $typedConfigManager;

  /**
   * Constructs the Config factory.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Language\Language
   *   (optional) The language for this configuration. The config factory will
   *   use it to override configuration data if language overrides are
   *   available.
   */
  public function __construct(StorageInterface $storage, EventDispatcher $event_dispatcher, TypedConfigManager $typed_config, Language $language = NULL) {
    $this->storage = $storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->typedConfigManager = $typed_config;
    $this->language = $language;
  }

  /**
   * Disable overrides when loading configuration objects.
   *
   * @return \Drupal\Core\Config\ConfigFactory
   *   The config factory object.
   */
  public function disableOverrides() {
    $this->useOverrides = FALSE;
    return $this;
  }

  /**
   * Enable overrides when loading configuration objects.
   *
   * @return \Drupal\Core\Config\ConfigFactory
   *   The config factory object.
   */
  public function enableOverrides() {
    $this->useOverrides = TRUE;
    return $this;
  }

  /**
   * Returns a configuration object for a given name.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  public function get($name) {
    global $conf;

    if ($config = $this->loadMultiple(array($name))) {
      return $config[$name];
    }
    else {
      $cache_key = $this->getCacheKey($name);
      // If the config object has been deleted it will already exist in the
      // cache but self::loadMultiple does not return such objects.
      // @todo Explore making ConfigFactory a listener to the config.delete
      //   event to reset the static cache when this occurs.
      if (!isset($this->cache[$cache_key])) {
        // If the configuration object does not exist in the configuration
        // storage or static cache create a new object and add it to the static
        // cache.
        $this->cache[$cache_key] = new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager, $this->language);

        if ($this->canOverride($name)) {
          // Get and apply any language overrides.
          if ($this->language) {
            $language_overrides = $this->storage->read($this->getLanguageConfigName($this->language->id, $name));
          }
          else {
            $language_overrides = FALSE;
          }
          if (is_array($language_overrides)) {
            $this->cache[$cache_key]->setLanguageOverride($language_overrides);
          }
          // Get and apply any module overrides.
          $module_overrides = $this->loadModuleOverrides(array($name));
          if (isset($module_overrides[$name])) {
            $this->cache[$cache_key]->setModuleOverride($module_overrides[$name]);
          }
          // Apply any settings.php overrides.
          if (isset($conf[$name])) {
            $this->cache[$cache_key]->setSettingsOverride($conf[$name]);
          }
        }
      }
      return $this->cache[$cache_key];
    }
  }

  /**
   * Returns a list of configuration objects for the given names.
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
    global $conf;

    $list = array();

    foreach ($names as $key => $name) {
      // @todo: Deleted configuration stays in $this->cache, only return
      //   configuration objects that are not new.
      $cache_key = $this->getCacheKey($name);
      if (isset($this->cache[$cache_key]) && !$this->cache[$cache_key]->isNew()) {
        $list[$name] = $this->cache[$cache_key];
        unset($names[$key]);
      }
    }

    // Pre-load remaining configuration files.
    if (!empty($names)) {
      // Initialise override information.
      $module_overrides = array();
      $language_names = array();

      if ($this->useOverrides) {
        // In order to make just one call to storage, add in language names.
        // Keep track of them separately, so we can get language override data
        // returned from storage and set it on new Config objects.
        $language_names = $this->getLanguageConfigNames($names);
      }

      $storage_data = $this->storage->readMultiple(array_merge($names, array_values($language_names)));

      if ($this->useOverrides && !empty($storage_data)) {
        // Only fire module override event if we have configuration to override.
        $module_overrides = $this->loadModuleOverrides($names);
      }

      foreach ($storage_data as $name => $data) {
        if (in_array($name, $language_names)) {
          // Language override configuration is used to override other
          // configuration. Therefore, when it has been added to the
          // $storage_data it is not statically cached in the config factory or
          // overridden in any way.
          continue;
        }
        $cache_key = $this->getCacheKey($name);

        $this->cache[$cache_key] = new Config($name, $this->storage, $this->eventDispatcher, $this->typedConfigManager, $this->language);
        $this->cache[$cache_key]->initWithData($data);
        if ($this->canOverride($name)) {
          if (isset($language_names[$name]) && isset($storage_data[$language_names[$name]])) {
            $this->cache[$cache_key]->setLanguageOverride($storage_data[$language_names[$name]]);
          }
          if (isset($module_overrides[$name])) {
            $this->cache[$cache_key]->setModuleOverride($module_overrides[$name]);
          }
          if (isset($conf[$name])) {
            $this->cache[$cache_key]->setSettingsOverride($conf[$name]);
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
  protected function loadModuleOverrides(array $names) {
    $configOverridesEvent = new ConfigModuleOverridesEvent($names, $this->language);
    $this->eventDispatcher->dispatch('config.module.overrides', $configOverridesEvent);
    return $configOverridesEvent->getOverrides();
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
      // Clear all cached configuration for this name.
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
    $this->storage->rename($old_name, $new_name);
    $old_cache_key = $this->getCacheKey($old_name);
    if (isset($this->cache[$old_cache_key])) {
      unset($this->cache[$old_cache_key]);
    }

    $new_cache_key = $this->getCacheKey($new_name);
    $this->cache[$new_cache_key] = new Config($new_name, $this->storage, $this->eventDispatcher, $this->typedConfigManager, $this->language);
    $this->cache[$new_cache_key]->load();
    return $this->cache[$new_cache_key];
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
  public function getCacheKey($name) {
    $can_override = $this->canOverride($name);
    $cache_key = $name . ':' . ($can_override ? 'overrides' : 'raw');

    if ($can_override && isset($this->language)) {
      $cache_key =  $cache_key . ':' . $this->language->id;
    }
    return $cache_key;
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
    return array_filter(array_keys($this->cache), function($key) use ($name) {
      // Return TRUE if the key starts with the configuration name.
      return strpos($key, $name . ':') === 0;
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

  /**
   * Set the language to be used in configuration overrides.
   *
   * @param \Drupal\Core\Language\Language $language
   *   The language object to be set on the config factory. Used to override
   *   configuration by language.
   *
   * @return \Drupal\Core\Config\ConfigFactory
   *   The config factory object.
   */
  public function setLanguage(Language $language = NULL) {
    $this->language = $language;
    return $this;
  }

  /**
   * Gets the language Used to override configuration.
   *
   * @return \Drupal\Core\Language\Language
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * Gets configuration names for this language.
   *
   * It will be the same name with a prefix depending on language code:
   * language.config.LANGCODE.NAME
   *
   * @param array $names
   *   A list of configuration object names.
   *
   * @return array
   *   The localized config names, keyed by configuration object name.
   */
  public function getLanguageConfigNames(array $names) {
    $language_names = array();
    if (isset($this->language)) {
      foreach ($names as $name) {
        if ($language_name = $this->getLanguageConfigName($this->language->id, $name)) {
          $language_names[$name] = $language_name;
        }
      }
    }
    return $language_names;
  }

  /**
   * Gets configuration name for the provided language.
   *
   * The name will be the same name with a prefix depending on language code:
   * language.config.LANGCODE.NAME
   *
   * @param string $langcode
   *   The language code.
   * @param string $name
   *   The name of the configuration object.
   *
   * @return bool|string
   *   The configuration name for configuration object providing overrides.
   *   Returns false if the name already starts with the language config prefix.
   */
  public function getLanguageConfigName($langcode, $name) {
    if (strpos($name, static::LANGUAGE_CONFIG_PREFIX) === 0) {
      return FALSE;
    }
    return static::LANGUAGE_CONFIG_PREFIX . '.' . $langcode . '.' . $name;
  }

  /**
   * Determines if a particular configuration object can be overridden.
   *
   * Language override configuration should not be overridden.
   *
   * @param string $name
   *   The name of the configuration object.
   *
   * @return bool
   *   TRUE if the configuration object can be overridden.
   */
  protected function canOverride($name) {
    return $this->useOverrides && !(strpos($name, static::LANGUAGE_CONFIG_PREFIX) === 0);
  }

  /**
   * Removes stale static cache entries when configuration is saved.
   *
   * @param ConfigEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigEvent $event) {
    // Ensure that the static cache contains up to date configuration objects by
    // replacing the data on any entries for the configuration object apart
    // from the one that references the actual config object being saved.
    $saved_config = $event->getConfig();
    foreach ($this->getCacheKeys($saved_config->getName()) as $cache_key) {
      $cached_config = $this->cache[$cache_key];
      if ($cached_config !== $saved_config) {
        $this->cache[$cache_key]->setData($saved_config->getRawData());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['config.save'][] = array('onConfigSave', 255);
    return $events;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\language\Config\LanguageConfigFactoryOverride.
 */

namespace Drupal\language\Config;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageDefault;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides language overrides for the configuration factory.
 */
class LanguageConfigFactoryOverride implements LanguageConfigFactoryOverrideInterface {

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $typedConfigManager;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The language object used to override configuration data.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $language;

  /**
   * Constructs the LanguageConfigFactoryOverride object.
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
  public function loadOverrides($names) {
    $data = array();
    $language_names = $this->getLanguageConfigNames($names);
    if ($language_names) {
      $data = $this->storage->readMultiple(array_values($language_names));
      // Re-key the data array to use configuration names rather than override
      // names.
      $prefix_length = strlen(static::LANGUAGE_CONFIG_PREFIX . '.' . $this->language->id) + 1;
      foreach ($data as $key => $value) {
        unset($data[$key]);
        $key = substr($key, $prefix_length);
        $data[$key] = $value;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getOverride($langcode, $name) {
    $override_name = $this->getLanguageConfigName($langcode, $name);
    $overrides = $this->storage->read($override_name);
    $config = new Config($override_name, $this->storage, $this->eventDispatcher, $this->typedConfigManager);
    if (!empty($overrides)) {
      $config->initWithData($overrides);
    }
    return $config;
  }

  /**
   * Generate a list of configuration names based on base names.
   *
   * @param array $names
   *   List of configuration names.
   *
   * @return array
   *   List of configuration names for language override files if applicable.
   */
  protected function getLanguageConfigNames(array $names) {
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
   * Get language override name for given language and configuration name.
   *
   * @param string $langcode
   *   Language code.
   * @param string $name
   *   Configuration name.
   *
   * @return bool|string
   *   Configuration name or FALSE if not applicable.
   */
  protected function getLanguageConfigName($langcode, $name) {
     if (strpos($name, static::LANGUAGE_CONFIG_PREFIX) === 0) {
      return FALSE;
    }
    return static::LANGUAGE_CONFIG_PREFIX . '.' . $langcode . '.' . $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return $this->language ? $this->language->id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage(Language $language = NULL) {
    $this->language = $language;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguageFromDefault(LanguageDefault $language_default = NULL) {
    $this->language = $language_default ? $language_default->get() : NULL;
    return $this;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\locale\LocaleConfigManager.
 */

namespace Drupal\locale;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;

/**
 * Manages localized configuration type plugins.
 */
class LocaleConfigManager {

  /**
   * A storage instance for reading configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * A storage instance for reading default configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $installStorage;

  /**
   * A string storage for reading and writing translations.
   */
  protected $localeStorage;

  /**
   * Array with preloaded string translations.
   *
   * @var array
   */
  protected $translations;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Whether or not configuration translations are currently being updated.
   *
   * @var bool
   */
  protected $isUpdating = FALSE;

  /**
   * Creates a new typed configuration manager.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The storage object to use for reading configuration data.
   * @param \Drupal\Core\Config\StorageInterface $install_storage
   *   The storage object to use for reading default configuration
   *   data.
   * @param \Drupal\locale\StringStorageInterface $locale_storage
   *   The locale storage to use for reading string translations.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(StorageInterface $config_storage, StorageInterface $install_storage, StringStorageInterface $locale_storage, ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config, ConfigurableLanguageManagerInterface $language_manager) {
    $this->configStorage = $config_storage;
    $this->installStorage = $install_storage;
    $this->localeStorage = $locale_storage;
    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config;
    $this->languageManager = $language_manager;
  }

  /**
   * Gets locale wrapper with typed configuration data.
   *
   * @param string $name
   *   Configuration object name.
   *
   * @return \Drupal\locale\LocaleTypedConfig
   *   Locale-wrapped configuration element.
   */
  public function get($name) {
    // Read default and current configuration data.
    $default = $this->installStorageRead($name);
    $updated = $this->configStorage->read($name);
    // We get only the data that didn't change from default.
    $data = $this->compareConfigData($default, $updated);
    $definition = $this->typedConfigManager->getDefinition($name);
    $data_definition = $this->typedConfigManager->buildDataDefinition($definition, $data);
    // Unless the configuration has a explicit language code we assume English.
    $langcode = isset($default['langcode']) ? $default['langcode'] : 'en';
    $wrapper = new LocaleTypedConfig($data_definition, $name, $langcode, $this, $this->typedConfigManager, $this->languageManager);
    $wrapper->setValue($data);
    return $wrapper;
  }

  /**
   * Compares default configuration with updated data.
   *
   * @param array $default
   *   Default configuration data.
   * @param array|false $updated
   *   Current configuration data, or FALSE if no configuration data existed.
   *
   * @return array
   *   The elements of default configuration that haven't changed.
   */
  protected function compareConfigData(array $default, $updated) {
    // Speed up comparison, specially for install operations.
    if ($default === $updated) {
      return $default;
    }
    $result = array();
    foreach ($default as $key => $value) {
      if (isset($updated[$key])) {
        if (is_array($value)) {
          $result[$key] = $this->compareConfigData($value, $updated[$key]);
        }
        elseif ($value === $updated[$key]) {
          $result[$key] = $value;
        }
      }
    }
    return $result;
  }

  /**
   * Saves translated configuration data.
   *
   * @param string $name
   *   Configuration object name.
   * @param string $langcode
   *   Language code.
   * @param array $data
   *   Configuration data to be saved, that will be only the translated values.
   */
  public function saveTranslationData($name, $langcode, array $data) {
    $this->isUpdating = TRUE;
    $this->languageManager->getLanguageConfigOverride($langcode, $name)->setData($data)->save();
    $this->isUpdating = FALSE;
  }

  /**
   * Deletes translated configuration data.
   *
   * @param string $name
   *   Configuration object name.
   * @param string $langcode
   *   Language code.
   */
  public function deleteTranslationData($name, $langcode) {
    $this->isUpdating = TRUE;
    $this->languageManager->getLanguageConfigOverride($langcode, $name)->delete();
    $this->isUpdating = FALSE;
  }

  /**
   * Gets configuration names associated with components.
   *
   * @param array $components
   *   (optional) Array of component lists indexed by type. If not present or it
   *   is an empty array, it will update all components.
   *
   * @return array
   *   Array of configuration object names.
   */
  public function getComponentNames(array $components) {
    $components = array_filter($components);
    if ($components) {
      $names = array();
      foreach ($components as $type => $list) {
        // InstallStorage::getComponentNames returns a list of folders keyed by
        // config name.
        $names = array_merge($names, $this->installStorageComponents($type, $list));
      }
      return $names;
    }
    else {
      return $this->installStorageAll();
    }
  }

  /**
   * Deletes configuration translations for uninstalled components.
   *
   * @param array $components
   *   Array with string identifiers.
   * @param array $langcodes
   *   Array of language codes.
   */
  public function deleteComponentTranslations(array $components, array $langcodes) {
    $this->isUpdating = TRUE;
    $names = $this->getComponentNames($components);
    if ($names && $langcodes) {
      foreach ($names as $name) {
        foreach ($langcodes as $langcode) {
          $this->deleteTranslationData($name, $langcode);
        }
      }
    }
    $this->isUpdating = FALSE;
  }

  /**
   * Gets configuration names associated with strings.
   *
   * @param array $lids
   *   Array with string identifiers.
   *
   * @return array
   *   Array of configuration object names.
   */
  public function getStringNames(array $lids) {
    $names = array();
    $locations = $this->localeStorage->getLocations(array('sid' => $lids, 'type' => 'configuration'));
    foreach ($locations as $location) {
      $names[$location->name] = $location->name;
    }
    return $names;
  }

  /**
   * Deletes configuration for language.
   *
   * @param string $langcode
   *   Language code to delete.
   */
  public function deleteLanguageTranslations($langcode) {
    $this->isUpdating = TRUE;
    $storage = $this->languageManager->getLanguageConfigOverrideStorage($langcode);
    foreach ($storage->listAll() as $name) {
      $this->languageManager->getLanguageConfigOverride($langcode, $name)->delete();
    }
    $this->isUpdating = FALSE;
  }

  /**
   * Translates string using the localization system.
   *
   * So far we only know how to translate strings from English so the source
   * string should be in English.
   * Unlike regular t() translations, strings will be added to the source
   * tables only if this is marked as default data.
   *
   * @param string $name
   *   Name of the configuration location.
   * @param string $langcode
   *   Language code to translate to.
   * @param string $source
   *   The source string, should be English.
   * @param string $context
   *   The string context.
   *
   * @return string|false
   *   Translated string if there is a translation, FALSE if not.
   */
  public function translateString($name, $langcode, $source, $context) {
    if ($source) {
      // If translations for a language have not been loaded yet.
      if (!isset($this->translations[$name][$langcode])) {
        // Preload all translations for this configuration name and language.
        $this->translations[$name][$langcode] = array();
        foreach ($this->localeStorage->getTranslations(array('language' => $langcode, 'type' => 'configuration', 'name' => $name)) as $string) {
          $this->translations[$name][$langcode][$string->context][$string->source] = $string;
        }
      }
      if (!isset($this->translations[$name][$langcode][$context][$source])) {
        // There is no translation of the source string in this config location
        // to this language for this context.
        if ($translation = $this->localeStorage->findTranslation(array('source' => $source, 'context' => $context, 'language' => $langcode))) {
          // Look for a translation of the string. It might have one, but not
          // be saved in this configuration location yet.
          // If the string has a translation for this context to this language,
          // save it in the configuration location so it can be looked up faster
          // next time.
          $this->localeStorage->createString((array) $translation)
            ->addLocation('configuration', $name)
            ->save();
        }
        else {
          // No translation was found. Add the source to the configuration
          // location so it can be translated, and the string is faster to look
          // for next time.
          $translation = $this->localeStorage
            ->createString(array('source' => $source, 'context' => $context))
            ->addLocation('configuration', $name)
            ->save();
        }

        // Add an entry, either the translation found, or a blank string object
        // to track the source string, to this configuration location, language,
        // and context.
        $this->translations[$name][$langcode][$context][$source] = $translation;
      }

      // Return the string only when the string object had a translation.
      if ($this->translations[$name][$langcode][$context][$source]->isTranslation()) {
        return $this->translations[$name][$langcode][$context][$source]->getString();
      }
    }
    return FALSE;
  }

  /**
   * Checks whether a language has configuration translation.
   *
   * @param string $name
   *   Configuration name.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   A language object.
   *
   * @return bool
   *   A boolean indicating if a language has configuration translations.
   */
  public function hasTranslation($name, LanguageInterface $language) {
    $translation = $this->languageManager->getLanguageConfigOverride($language->getId(), $name);
    return !$translation->isNew();
  }

  /**
   * Indicates whether configuration translations are currently being updated.
   *
   * @return bool
   *   Whether or not configuration translations are currently being updated.
   */
  public function isUpdatingConfigTranslations() {
    return $this->isUpdating;
  }

  /**
   * Read a configuration from install storage or default languages.
   *
   * @param string $name
   *   Configuration object name.
   *
   * @return array
   *   Configuration data from install storage or default language.
   */
  protected function installStorageRead($name) {
    if ($this->installStorage->exists($name)) {
      return $this->installStorage->read($name);
    }
    elseif (strpos($name, 'language.entity.') === 0) {
      // Simulate default languages as if they were shipped as default
      // configuration.
      $langcode = str_replace('language.entity.', '', $name);
      $predefined_languages = $this->languageManager->getStandardLanguageList();
      if (isset($predefined_languages[$langcode])) {
        $data = $this->configStorage->read($name);
        $data['label'] = $predefined_languages[$langcode][0];
        return $data;
      }
    }
  }

  /**
   * Return the list of configuration in install storage and current languages.
   *
   * @return array
   *   List of configuration in install storage and current languages.
   */
  protected function installStorageAll() {
    $languages = $this->predefinedConfiguredLanguages();
    return array_unique(array_merge($this->installStorage->listAll(), $languages));
  }

  /**
   * Get all configuration names and folders for a list of modules or themes.
   *
   * @param string $type
   *   Type of components: 'module' | 'theme' | 'profile'
   * @param array $list
   *   Array of theme or module names.
   *
   * @return array
   *   Configuration names provided by that component. In case of language
   *   module this list is extended with configured languages that have
   *   predefined names as well.
   */
  protected function installStorageComponents($type, array $list) {
    $names = array_keys($this->installStorage->getComponentNames($type, $list));
    if ($type == 'module' && in_array('language', $list)) {
      $languages = $this->predefinedConfiguredLanguages();
      $names = array_unique(array_merge($names, $languages));
    }
    return $names;
  }

  /**
   * Compute the list of configuration names that match predefined languages.
   *
   * @return array
   *   The list of configuration names that match predefined languages.
   */
  protected function predefinedConfiguredLanguages() {
    $names = $this->configStorage->listAll('language.entity.');
    $predefined_languages = $this->languageManager->getStandardLanguageList();
    foreach ($names as $id => $name) {
      $langcode = str_replace('language.entity.', '', $name);
      if (!isset($predefined_languages[$langcode])) {
        unset($names[$id]);
      }
    }
    return array_values($names);
  }

}

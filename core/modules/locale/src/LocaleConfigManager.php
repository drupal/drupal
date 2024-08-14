<?php

namespace Drupal\locale;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;

/**
 * Manages configuration supported in part by interface translation.
 *
 * This manager is responsible to update configuration overrides and active
 * translations when interface translation data changes. This allows Drupal to
 * translate user roles, views, blocks, etc. after Drupal has been installed
 * using the locale module's storage. When translations change in locale,
 * LocaleConfigManager::updateConfigTranslations() is invoked to update the
 * corresponding storage of the translation in the original config object or an
 * override.
 *
 * In turn when translated configuration or configuration language overrides are
 * changed, it is the responsibility of LocaleConfigSubscriber to update locale
 * storage.
 *
 * By design locale module only deals with sources in English.
 *
 * @see \Drupal\locale\LocaleConfigSubscriber
 */
class LocaleConfigManager {

  /**
   * The storage instance for reading configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The string storage for reading and writing translations.
   *
   * @var \Drupal\locale\StringStorageInterface
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
   * Whether or not configuration translations are being updated from locale.
   *
   * @var bool
   *
   * @see self::isUpdatingFromLocale()
   */
  protected $isUpdatingFromLocale = FALSE;

  /**
   * The locale default config storage instance.
   *
   * @var \Drupal\locale\LocaleDefaultConfigStorage
   */
  protected $defaultConfigStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Creates a new typed configuration manager.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The storage object to use for reading configuration data.
   * @param \Drupal\locale\StringStorageInterface $locale_storage
   *   The locale storage to use for reading string translations.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\locale\LocaleDefaultConfigStorage $default_config_storage
   *   The locale default configuration storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function __construct(StorageInterface $config_storage, StringStorageInterface $locale_storage, ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config, ConfigurableLanguageManagerInterface $language_manager, LocaleDefaultConfigStorage $default_config_storage, ConfigManagerInterface $config_manager) {
    $this->configStorage = $config_storage;
    $this->localeStorage = $locale_storage;
    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config;
    $this->languageManager = $language_manager;
    $this->defaultConfigStorage = $default_config_storage;
    $this->configManager = $config_manager;
  }

  /**
   * Gets array of translated strings for Locale translatable configuration.
   *
   * @param string $name
   *   Configuration object name.
   *
   * @return array
   *   Array of Locale translatable elements of the default configuration in
   *   $name.
   */
  public function getTranslatableDefaultConfig($name) {
    if ($this->isSupported($name)) {
      // Create typed configuration wrapper based on install storage data.
      $data = $this->defaultConfigStorage->read($name);
      $typed_config = $this->typedConfigManager->createFromNameAndData($name, $data);
      if ($typed_config instanceof TraversableTypedDataInterface) {
        return $this->getTranslatableData($typed_config);
      }
    }
    return [];
  }

  /**
   * Gets translatable configuration data for a typed configuration element.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   Typed configuration element.
   *
   * @return array|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   A nested array matching the exact structure under $element with only the
   *   elements that are translatable wrapped into a TranslatableMarkup. If the
   *   provided $element is not traversable, the return value is a single
   *   TranslatableMarkup.
   */
  protected function getTranslatableData(TypedDataInterface $element) {
    $translatable = [];
    if ($element instanceof TraversableTypedDataInterface) {
      foreach ($element as $key => $property) {
        $value = $this->getTranslatableData($property);
        if (!empty($value)) {
          $translatable[$key] = $value;
        }
      }
    }
    else {
      // Something is only translatable by Locale if there is a string in the
      // first place.
      $value = $element->getValue();
      $definition = $element->getDataDefinition();
      if (!empty($definition['translatable']) && $value !== '' && $value !== NULL) {
        $options = [];
        if (isset($definition['translation context'])) {
          $options['context'] = $definition['translation context'];
        }
        return new TranslatableMarkup($value, [], $options);
      }
    }
    return $translatable;
  }

  /**
   * Process the translatable data array with a given language.
   *
   * If the given language is translatable, will return the translated copy
   * which will only contain strings that had translations. If the given
   * language is English and is not translatable, will return a simplified
   * array of the English source strings only.
   *
   * @param string $name
   *   The configuration name.
   * @param array $active
   *   The active configuration data.
   * @param array|\Drupal\Core\StringTranslation\TranslatableMarkup[] $translatable
   *   The translatable array structure. A nested array matching the exact
   *   structure under of the default configuration for $name with only the
   *   elements that are translatable wrapped into a TranslatableMarkup.
   * @param string $langcode
   *   The language code to process the array with.
   *
   * @return array
   *   Processed translatable data array. Will only contain translations
   *   different from source strings or in case of untranslatable English, the
   *   source strings themselves.
   *
   * @see self::getTranslatableData()
   */
  protected function processTranslatableData($name, array $active, array $translatable, $langcode) {
    $translated = [];
    foreach ($translatable as $key => $item) {
      if (!isset($active[$key])) {
        continue;
      }
      if (is_array($item)) {
        // Only add this key if there was a translated value underneath.
        $value = $this->processTranslatableData($name, $active[$key], $item, $langcode);
        if (!empty($value)) {
          $translated[$key] = $value;
        }
      }
      else {
        if (locale_is_translatable($langcode)) {
          $value = $this->translateString($name, $langcode, $item->getUntranslatedString(), $item->getOption('context'));
        }
        else {
          $value = $item->getUntranslatedString();
        }
        if (!empty($value)) {
          $translated[$key] = $value;
        }
      }
    }
    return $translated;
  }

  /**
   * Saves translated configuration override.
   *
   * @param string $name
   *   Configuration object name.
   * @param string $langcode
   *   Language code.
   * @param array $data
   *   Configuration data to be saved, that will be only the translated values.
   */
  protected function saveTranslationOverride($name, $langcode, array $data) {
    $this->isUpdatingFromLocale = TRUE;
    $this->languageManager->getLanguageConfigOverride($langcode, $name)->setData($data)->save();
    $this->isUpdatingFromLocale = FALSE;
  }

  /**
   * Saves translated configuration data.
   *
   * @param string $name
   *   Configuration object name.
   * @param array $data
   *   Configuration data to be saved with translations merged in.
   */
  protected function saveTranslationActive($name, array $data) {
    $this->isUpdatingFromLocale = TRUE;
    $this->configFactory->getEditable($name)->setData($data)->save();
    $this->isUpdatingFromLocale = FALSE;
  }

  /**
   * Deletes translated configuration data.
   *
   * @param string $name
   *   Configuration object name.
   * @param string $langcode
   *   Language code.
   */
  protected function deleteTranslationOverride($name, $langcode) {
    $this->isUpdatingFromLocale = TRUE;
    $this->languageManager->getLanguageConfigOverride($langcode, $name)->delete();
    $this->isUpdatingFromLocale = FALSE;
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
  public function getComponentNames(array $components = []) {
    $components = array_filter($components);
    if ($components) {
      $names = [];
      foreach ($components as $type => $list) {
        // InstallStorage::getComponentNames returns a list of folders keyed by
        // config name.
        $names = array_merge($names, $this->defaultConfigStorage->getComponentNames($type, $list));
      }
      return $names;
    }
    else {
      return $this->defaultConfigStorage->listAll();
    }
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
    $names = [];
    $locations = $this->localeStorage->getLocations(['sid' => $lids, 'type' => 'configuration']);
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
    $this->isUpdatingFromLocale = TRUE;
    $storage = $this->languageManager->getLanguageConfigOverrideStorage($langcode);
    foreach ($storage->listAll() as $name) {
      $this->languageManager->getLanguageConfigOverride($langcode, $name)->delete();
    }
    $this->isUpdatingFromLocale = FALSE;
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
        $this->translations[$name][$langcode] = [];
        foreach ($this->localeStorage->getTranslations(['language' => $langcode, 'type' => 'configuration', 'name' => $name]) as $string) {
          $this->translations[$name][$langcode][$string->context][$string->source] = $string;
        }
      }
      if (!isset($this->translations[$name][$langcode][$context][$source])) {
        // There is no translation of the source string in this config location
        // to this language for this context.
        if ($translation = $this->localeStorage->findTranslation(['source' => $source, 'context' => $context, 'language' => $langcode])) {
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
            ->createString(['source' => $source, 'context' => $context])
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
   * Reset static cache of configuration string translations.
   *
   * @return $this
   */
  public function reset() {
    $this->translations = [];
    return $this;
  }

  /**
   * Get the translation object for the given source/context and language.
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
   * @return \Drupal\locale\TranslationString|false
   *   The translation object if the string was not empty or FALSE otherwise.
   */
  public function getStringTranslation($name, $langcode, $source, $context) {
    if ($source) {
      $this->translateString($name, $langcode, $source, $context);
      if ($string = $this->translations[$name][$langcode][$context][$source]) {
        if (!$string->isTranslation()) {
          $conditions = ['lid' => $string->lid, 'language' => $langcode];
          $translation = $this->localeStorage->createTranslation($conditions);
          $this->translations[$name][$langcode][$context][$source] = $translation;
          return $translation;
        }
        else {
          return $string;
        }
      }
    }
    return FALSE;
  }

  /**
   * Checks whether a language has configuration translation.
   *
   * @param string $name
   *   Configuration name.
   * @param string $langcode
   *   A language code.
   *
   * @return bool
   *   A boolean indicating if a language has configuration translations.
   */
  public function hasTranslation($name, $langcode) {
    $translation = $this->languageManager->getLanguageConfigOverride($langcode, $name);
    return !$translation->isNew();
  }

  /**
   * Returns the original language code for this shipped configuration.
   *
   * @param string $name
   *   The configuration name.
   *
   * @return null|string
   *   Language code of the default configuration for $name. If the default
   *   configuration data for $name did not contain a language code, it is
   *   assumed to be English. The return value is NULL if no such default
   *   configuration exists.
   */
  public function getDefaultConfigLangcode($name) {
    // Config entities that do not have the 'default_config_hash' cannot be
    // shipped configuration regardless of whether there is a name match.
    // configurable_language entities are a special case since they can be
    // translated regardless of whether they are shipped if they in the standard
    // language list.
    $config_entity_type = $this->configManager->getEntityTypeIdByName($name);
    if (!$config_entity_type || $config_entity_type === 'configurable_language'
      || !empty($this->configFactory->get($name)->get('_core.default_config_hash'))
    ) {
      $shipped = $this->defaultConfigStorage->read($name);
      if (!empty($shipped)) {
        return !empty($shipped['langcode']) ? $shipped['langcode'] : 'en';
      }
    }
    return NULL;
  }

  /**
   * Returns the current language code for this active configuration.
   *
   * @param string $name
   *   The configuration name.
   *
   * @return null|string
   *   Language code of the current active configuration for $name. If the
   *   configuration data for $name did not contain a language code, it is
   *   assumed to be English. The return value is NULL if no such active
   *   configuration exists.
   */
  public function getActiveConfigLangcode($name) {
    $active = $this->configStorage->read($name);
    if (!empty($active)) {
      return !empty($active['langcode']) ? $active['langcode'] : 'en';
    }
  }

  /**
   * Whether the given configuration is supported for interface translation.
   *
   * @param string $name
   *   The configuration name.
   *
   * @return bool
   *   TRUE if interface translation is supported.
   */
  public function isSupported($name) {
    return $this->getDefaultConfigLangcode($name) == 'en' && $this->configStorage->read($name);
  }

  /**
   * Indicates whether configuration translations are being updated from locale.
   *
   * @return bool
   *   Whether or not configuration translations are currently being updated.
   *   If TRUE, LocaleConfigManager is in control of the process and the
   *   reference data is locale's storage. Changes made to active configuration
   *   and overrides in this case should not feed back to locale storage.
   *   On the other hand, when not updating from locale and configuration
   *   translations change, we need to feed back to the locale storage.
   */
  public function isUpdatingTranslationsFromLocale() {
    return $this->isUpdatingFromLocale;
  }

  /**
   * Updates all configuration translations for the names / languages provided.
   *
   * To be used when interface translation changes result in the need to update
   * configuration translations to keep them in sync.
   *
   * @param array $names
   *   Array of names of configuration objects to update.
   * @param array $langcodes
   *   (optional) Array of language codes to update. Defaults to all
   *   configurable languages.
   *
   * @return int
   *   Total number of configuration override and active configuration objects
   *   updated (saved or removed).
   */
  public function updateConfigTranslations(array $names, array $langcodes = []) {
    $langcodes = $langcodes ? $langcodes : array_keys($this->languageManager->getLanguages());
    $count = 0;
    foreach ($names as $name) {
      $translatable = $this->getTranslatableDefaultConfig($name);
      if (empty($translatable)) {
        // If there is nothing translatable in this configuration or not
        // supported, skip it.
        continue;
      }

      $active_langcode = $this->getActiveConfigLangcode($name);
      $active = $this->configStorage->read($name);

      foreach ($langcodes as $langcode) {
        $processed = $this->processTranslatableData($name, $active, $translatable, $langcode);
        // If the language code is not the same as the active storage
        // language, we should update the configuration override.
        if ($langcode != $active_langcode) {
          $override = $this->languageManager->getLanguageConfigOverride($langcode, $name);
          // Filter out locale managed configuration keys so that translations
          // removed from Locale will be reflected in the config override.
          $data = $this->filterOverride($override->get(), $translatable);
          if (!empty($processed)) {
            // Merge in the Locale managed translations with existing data.
            $data = NestedArray::mergeDeepArray([$data, $processed], TRUE);
          }
          if (empty($data) && !$override->isNew()) {
            // The configuration override contains Locale overrides that no
            // longer exist.
            $this->deleteTranslationOverride($name, $langcode);
            $count++;
          }
          elseif (!empty($data)) {
            // Update translation data in configuration override.
            $this->saveTranslationOverride($name, $langcode, $data);
            $count++;
          }
        }
        elseif (locale_is_translatable($langcode)) {
          // If the language code is the active storage language, we should
          // update. If it is English, we should only update if English is also
          // translatable.
          $active = NestedArray::mergeDeepArray([$active, $processed], TRUE);
          $this->saveTranslationActive($name, $active);
          $count++;
        }
      }
    }
    return $count;
  }

  /**
   * Filters override data based on default translatable items.
   *
   * @param array $override_data
   *   Configuration override data.
   * @param array $translatable
   *   Translatable data array. @see self::getTranslatableData()
   *
   * @return array
   *   Nested array of any items of $override_data which did not have keys in
   *   $translatable. May be empty if $override_data only had items which were
   *   also in $translatable.
   */
  protected function filterOverride(array $override_data, array $translatable) {
    $filtered_data = [];
    foreach ($override_data as $key => $value) {
      if (isset($translatable[$key])) {
        // If the translatable default configuration has this key, look further
        // for subkeys or ignore this element for scalar values.
        if (is_array($value)) {
          $value = $this->filterOverride($value, $translatable[$key]);
          if (!empty($value)) {
            $filtered_data[$key] = $value;
          }
        }
      }
      else {
        // If this key was not in the translatable default configuration,
        // keep it.
        $filtered_data[$key] = $value;
      }
    }
    return $filtered_data;
  }

  /**
   * Updates default configuration when new modules or themes are installed.
   */
  public function updateDefaultConfigLangcodes() {
    $this->isUpdatingFromLocale = TRUE;
    // Need to rewrite some default configuration language codes if the default
    // site language is not English.
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    if ($default_langcode != 'en') {
      // Update active configuration copies of all prior shipped configuration if
      // they are still English. It is not enough to change configuration shipped
      // with the components just installed, because installing a component such
      // as views may bring in default configuration from prior components.
      $names = $this->getComponentNames();
      foreach ($names as $name) {
        $config = $this->configFactory->reset($name)->getEditable($name);
        // Should only update if still exists in active configuration. If locale
        // module is enabled later, then some configuration may not exist anymore.
        if (!$config->isNew()) {
          $typed_config = $this->typedConfigManager->createFromNameAndData($config->getName(), $config->getRawData());
          $langcode = $config->get('langcode');
          // Only set a `langcode` if this config actually contains translatable
          // data.
          // @see \Drupal\Core\Config\Plugin\Validation\Constraint\LangcodeRequiredIfTranslatableValuesConstraint
          if (!empty($this->getTranslatableData($typed_config)) && (empty($langcode) || $langcode == 'en')) {
            $config->set('langcode', $default_langcode)->save();
          }
        }
      }
    }
    $this->isUpdatingFromLocale = FALSE;
  }

}

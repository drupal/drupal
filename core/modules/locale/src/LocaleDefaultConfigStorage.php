<?php

/**
 * @file
 * Contains \Drupal\locale\LocaleDefaultConfigStorage.
 */

namespace Drupal\locale;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;

/**
 * Provides access to default configuration for locale integration.
 *
 * Allows unified access to default configuration from one of three sources:
 * - Required default configuration (config/install/*)
 * - Optional default configuration (config/optional/*)
 * - Predefined languages mocked as default configuration (list defined in
 *   LocaleConfigManagerInterface::getStandardLanguageList())
 *
 * These sources are considered equal in terms of how locale module interacts
 * with them for translation. Their translatable source strings are exposed
 * for interface translation and participate in remote translation updates.
 */
class LocaleDefaultConfigStorage {

  /**
   * The storage instance for reading configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The storage instance for reading required default configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $requiredInstallStorage;

  /**
   * The storage instance for reading optional default configuration data.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $optionalInstallStorage;

  /**
   * Constructs a LocaleDefaultConfigStorage.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The storage object to use for reading configuration data.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(StorageInterface $config_storage, ConfigurableLanguageManagerInterface $language_manager) {
    $this->configStorage = $config_storage;
    $this->languageManager = $language_manager;

    $this->requiredInstallStorage = new InstallStorage();
    $this->optionalInstallStorage = new InstallStorage(InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
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
  public function read($name) {
    if ($this->requiredInstallStorage->exists($name)) {
      return $this->requiredInstallStorage->read($name);
    }
    elseif ($this->optionalInstallStorage->exists($name)) {
      return $this->optionalInstallStorage->read($name);
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
  public function listAll() {
    $languages = $this->predefinedConfiguredLanguages();
    return array_unique(
      array_merge(
        $this->requiredInstallStorage->listAll(),
        $this->optionalInstallStorage->listAll(),
        $languages
      )
    );
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
  public function getComponentNames($type, array $list) {
    $names = array_unique(
      array_merge(
        array_keys($this->requiredInstallStorage->getComponentNames($type, $list)),
        array_keys($this->optionalInstallStorage->getComponentNames($type, $list))
      )
    );
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


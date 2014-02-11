<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Config.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigNameException;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\Language\Language;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the default configuration object.
 */
class Config extends DependencySerialization {

  /**
   * The maximum length of a configuration object name.
   *
   * Many filesystems (including HFS, NTFS, and ext4) have a maximum file name
   * length of 255 characters. To ensure that no configuration objects
   * incompatible with this limitation are created, we enforce a maximum name
   * length of 250 characters (leaving 5 characters for the file extension).
   *
   * @see http://en.wikipedia.org/wiki/Comparison_of_file_systems
   */
  const MAX_NAME_LENGTH = 250;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The language object used to override configuration data.
   *
   * @var Drupal\Core\Language\Language
   */
  protected $language;

  /**
   * The name of the configuration object.
   *
   * @var string
   */
  protected $name;

  /**
   * Whether the configuration object is new or has been saved to the storage.
   *
   * @var bool
   */
  protected $isNew = TRUE;

  /**
   * The data of the configuration object.
   *
   * @var array
   */
  protected $data = array();

  /**
   * The original data of the configuration object.
   *
   * @var array
   */
  protected $originalData = array();

  /**
   * The current runtime data.
   *
   * The configuration data from storage merged with language, module and
   * settings overrides.
   *
   * @var array
   */
  protected $overriddenData;

  /**
   * The current language overrides.
   *
   * @var array
   */
  protected $languageOverrides;

  /**
   * The current module overrides.
   *
   * @var array
   */
  protected $moduleOverrides;

  /**
   * The storage used to load and save this configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The config schema wrapper object for this configuration object.
   *
   * @var \Drupal\Core\Config\Schema\Element
   */
  protected $schemaWrapper;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $typedConfigManager;

  /**
   * Constructs a configuration object.
   *
   * @param string $name
   *   The name of the configuration object being constructed.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A storage controller object to use for reading and writing the
   *   configuration data.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config
   *   The typed configuration manager service.
   * @param \Drupal\Core\Language\Language $language
   *   The language object used to override configuration data.
   */
  public function __construct($name, StorageInterface $storage, EventDispatcherInterface $event_dispatcher, TypedConfigManager $typed_config, Language $language = NULL) {
    $this->name = $name;
    $this->storage = $storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->typedConfigManager = $typed_config;
    $this->language = $language;
  }

  /**
   * Initializes a configuration object with pre-loaded data.
   *
   * @param array $data
   *   Array of loaded data for this configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function initWithData(array $data) {
    $this->settingsOverrides = array();
    $this->languageOverrides = array();
    $this->moduleOverrides = array();
    $this->isNew = FALSE;
    $this->replaceData($data);
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * Returns the name of this configuration object.
   *
   * @return string
   *   The name of the configuration object.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of this configuration object.
   *
   * @param string $name
   *  The name of the configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * Validates the configuration object name.
   *
   * @param string $name
   *  The name of the configuration object.
   *
   * @throws \Drupal\Core\Config\ConfigNameException
   *
   * @see Config::MAX_NAME_LENGTH
   */
  public static function validateName($name) {
    // The name must be namespaced by owner.
    if (strpos($name, '.') === FALSE) {
      throw new ConfigNameException(format_string('Missing namespace in Config object name @name.', array(
        '@name' => $name,
      )));
    }
    // The name must be shorter than Config::MAX_NAME_LENGTH characters.
    if (strlen($name) > self::MAX_NAME_LENGTH) {
      throw new ConfigNameException(format_string('Config object name @name exceeds maximum allowed length of @length characters.', array(
        '@name' => $name,
        '@length' => self::MAX_NAME_LENGTH,
      )));
    }

    // The name must not contain any of the following characters:
    // : ? * < > " ' / \
    if (preg_match('/[:?*<>"\'\/\\\\]/', $name)) {
      throw new ConfigNameException(format_string('Invalid character in Config object name @name.', array(
        '@name' => $name,
      )));
    }
  }

  /**
   * Returns whether this configuration object is new.
   *
   * @return bool
   *   TRUE if this configuration object does not exist in storage.
   */
  public function isNew() {
    return $this->isNew;
  }

  /**
   * Gets data from this configuration object.
   *
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *   For instance in the following configuration array:
   *   @code
   *   array(
   *     'foo' => array(
   *       'bar' => 'baz',
   *     ),
   *   );
   *   @endcode
   *   A key of 'foo.bar' would return the string 'baz'. However, a key of 'foo'
   *   would return array('bar' => 'baz').
   *   If no key is specified, then the entire data array is returned.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function get($key = '') {
    if (!isset($this->overriddenData)) {
      $this->setOverriddenData();
    }
    if (empty($key)) {
      return $this->overriddenData;
    }
    else {
      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return isset($this->overriddenData[$key]) ? $this->overriddenData[$key] : NULL;
      }
      else {
        $value = NestedArray::getValue($this->overriddenData, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }
    }
  }

  /**
   * Replaces the data of this configuration object.
   *
   * @param array $data
   *   The new configuration data.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function setData(array $data) {
    $this->replaceData($data);
    return $this;
  }

  /**
   * Replaces the data of this configuration object.
   *
   * This function is separate from setData() to avoid load() state tracking.
   * A load() would destroy the replaced data (for example on import). Do not
   * call set() when inside load().
   *
   * @param array $data
   *   The new configuration data.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  protected function replaceData(array $data) {
    $this->data = $data;
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Sets settings.php overrides for this configuration object.
   *
   * The overridden data only applies to this configuration object.
   *
   * @param array $data
   *   The overridden values of the configuration data.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function setSettingsOverride(array $data) {
    $this->settingsOverrides = $data;
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Sets module overrides for this configuration object.
   *
   * @param array $data
   *   The overridden values of the configuration data.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function setModuleOverride(array $data) {
    $this->moduleOverrides = $data;
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Sets language overrides for this configuration object.
   *
   * @param array $data
   *   The overridden values of the configuration data.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function setLanguageOverride(array $data) {
    $this->languageOverrides = $data;
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Sets the current data for this configuration object.
   *
   * Configuration overrides operate at three distinct layers: language, modules
   * and settings.php, with the last of these taking precedence. Overrides in
   * settings.php take precedence over values provided by modules. Overrides
   * provided by modules take precedence over language.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  protected function setOverriddenData() {
    $this->overriddenData = $this->data;
    if (isset($this->languageOverrides) && is_array($this->languageOverrides)) {
      $this->overriddenData = NestedArray::mergeDeepArray(array($this->overriddenData, $this->languageOverrides), TRUE);
    }
    if (isset($this->moduleOverrides) && is_array($this->moduleOverrides)) {
      $this->overriddenData = NestedArray::mergeDeepArray(array($this->overriddenData, $this->moduleOverrides), TRUE);
    }
    if (isset($this->settingsOverrides) && is_array($this->settingsOverrides)) {
      $this->overriddenData = NestedArray::mergeDeepArray(array($this->overriddenData, $this->settingsOverrides), TRUE);
    }
    return $this;
  }

  /**
   * Resets the current data, so overrides are re-applied.
   *
   * This method should be called after the original data or the overridden data
   * has been changed.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  protected function resetOverriddenData() {
    unset($this->overriddenData);
    return $this;
  }

  /**
   * Sets a value in this configuration object.
   *
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function set($key, $value) {
    // The dot/period is a reserved character; it may appear between keys, but
    // not within keys.
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      $this->data[$key] = $value;
    }
    else {
      NestedArray::setValue($this->data, $parts, $value);
    }
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Unsets a value in this configuration object.
   *
   * @param string $key
   *   Name of the key whose value should be unset.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function clear($key) {
    $parts = explode('.', $key);
    if (count($parts) == 1) {
      unset($this->data[$key]);
    }
    else {
      NestedArray::unsetValue($this->data, $parts);
    }
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Saves the configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function save() {
    // Validate the configuration object name before saving.
    static::validateName($this->name);

    // If there is a schema for this configuration object, cast all values to
    // conform to the schema.
    if ($this->typedConfigManager->hasConfigSchema($this->name)) {
      // Ensure that the schema wrapper has the latest data.
      $this->schemaWrapper = NULL;
      foreach ($this->data as $key => $value) {
        $this->data[$key] = $this->castValue($key, $value);
      }
    }

    $this->storage->write($this->name, $this->data);
    $this->isNew = FALSE;
    $this->eventDispatcher->dispatch(ConfigEvents::SAVE, new ConfigCrudEvent($this));
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * Deletes the configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function delete() {
    // @todo Consider to remove the pruning of data for Config::delete().
    $this->data = array();
    $this->storage->delete($this->name);
    $this->isNew = TRUE;
    $this->resetOverriddenData();
    $this->eventDispatcher->dispatch(ConfigEvents::DELETE, new ConfigCrudEvent($this));
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * Retrieves the storage used to load and save this configuration object.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage object.
   */
  public function getStorage() {
    return $this->storage;
  }

  /**
   * Merges data into a configuration object.
   *
   * @param array $data_to_merge
   *   An array containing data to merge.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function merge(array $data_to_merge) {
    // Preserve integer keys so that configuration keys are not changed.
    $this->replaceData(NestedArray::mergeDeepArray(array($this->data, $data_to_merge), TRUE));
    return $this;
  }

  /**
   * Gets the schema wrapper for the whole configuration object.
   *
   * The schema wrapper is dependent on the configuration name and the whole
   * data structure, so if the name or the data changes in any way, the wrapper
   * should be reset.
   *
   * @return \Drupal\Core\Config\Schema\Element
   */
  protected function getSchemaWrapper() {
    if (!isset($this->schemaWrapper)) {
      $definition = $this->typedConfigManager->getDefinition($this->name);
      $this->schemaWrapper = $this->typedConfigManager->create($definition, $this->data);
    }
    return $this->schemaWrapper;
  }

  /**
   * Casts the value to correct data type using the configuration schema.
   *
   * @param string $key
   *   A string that maps to a key within the configuration data.
   * @param string $value
   *   Value to associate with the key.
   *
   * @return mixed
   *   The value cast to the type indicated in the schema.
   *
   * @throws \Drupal\Core\Config\UnsupportedDataTypeConfigException
   *   Exception on unsupported/undefined data type deducted.
   */
  protected function castValue($key, $value) {
    if ($value === NULL) {
      $value = NULL;
    }
    elseif (is_scalar($value)) {
      try {
        $element = $this->getSchemaWrapper()->get($key);
        if ($element instanceof PrimitiveInterface) {
          // Special handling for integers and floats since the configuration
          // system is primarily concerned with saving values from the Form API
          // we have to special case the meaning of an empty string for numeric
          // types. In PHP this would be casted to a 0 but for the purposes of
          // configuration we need to treat this as a NULL.
          if ($value === '' && ($element instanceof IntegerInterface || $element instanceof FloatInterface)) {
            $value = NULL;
          }
          else {
            $value = $element->getCastedValue();
          }
        }
        else {
          // Config only supports primitive data types. If the config schema
          // does define a type $element will be an instance of
          // \Drupal\Core\Config\Schema\Property. Convert it to string since it
          // is the safest possible type.
          $value = $element->getString();
        }
      }
      catch (SchemaIncompleteException $e) {
        // @todo throw an exception due to an incomplete schema.
        // Fix as part of https://drupal.org/node/2183983.
      }
    }
    else {
      // Throw exception on any non-scalar or non-array value.
      if (!is_array($value)) {
        throw new UnsupportedDataTypeConfigException(String::format('Invalid data type for config element @name:@key', array(
          '@name' => $this->getName(),
          '@key' => $key,
        )));
      }
      // Recurse into any nested keys.
      foreach ($value as $nested_value_key => $nested_value) {
        $value[$nested_value_key] = $this->castValue($key . '.' . $nested_value_key, $nested_value);
      }
    }
    return $value;
  }

  /**
   * Returns the language object for this Config object.
   *
   * @return \Drupal\Core\Language\Language
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * Gets the raw data without overrides.
   *
   * @return array
   *   The raw data.
   */
  public function getRawData() {
    return $this->data;
  }

  /**
   * Gets original data from this configuration object.
   *
   * Original data is the data as it is immediately after loading from
   * configuration storage before any changes. If this is a new configuration
   * object it will be an empty array.
   *
   * @see \Drupal\Core\Config\Config::get()
   *
   * @param string $key
   *   A string that maps to a key within the configuration data.
   * @param bool $apply_overrides
   *   Apply any overrides to the original data. Defaults to TRUE.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function getOriginal($key = '', $apply_overrides = TRUE) {
    $original_data = $this->originalData;
    if ($apply_overrides) {
      // Apply overrides.
      if (isset($this->languageOverrides) && is_array($this->languageOverrides)) {
        $original_data = NestedArray::mergeDeepArray(array($original_data, $this->languageOverrides), TRUE);
      }
      if (isset($this->moduleOverrides) && is_array($this->moduleOverrides)) {
        $original_data = NestedArray::mergeDeepArray(array($original_data, $this->moduleOverrides), TRUE);
      }
      if (isset($this->settingsOverrides) && is_array($this->settingsOverrides)) {
        $original_data = NestedArray::mergeDeepArray(array($original_data, $this->settingsOverrides), TRUE);
      }
    }

    if (empty($key)) {
      return $original_data;
    }
    else {
      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return isset($original_data[$key]) ? $original_data[$key] : NULL;
      }
      else {
        $value = NestedArray::getValue($original_data, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }
    }
  }
}


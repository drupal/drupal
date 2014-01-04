<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Config.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigNameException;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;

/**
 * Defines the default configuration object.
 */
class Config {

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
  protected $data;

  /**
   * The current runtime data ($data + $overrides from Config Context).
   *
   * @var array
   */
  protected $overriddenData;

  /**
   * The storage used to load and save this configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The configuration context used for this configuration object.
   *
   * @var \Drupal\Core\Config\Context\ContextInterface
   */
  protected $context;

  /**
   * Whether the configuration object has already been loaded.
   *
   * @var bool
   */
  protected $isLoaded = FALSE;

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
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context used for this configuration object.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config
   *   The typed configuration manager service.
   */
  public function __construct($name, StorageInterface $storage, ContextInterface $context, TypedConfigManager $typed_config) {
    $this->name = $name;
    $this->storage = $storage;
    $this->context = $context;
    $this->typedConfigManager = $typed_config;
  }

  /**
   * Initializes a configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function init() {
    $this->isLoaded = FALSE;
    $this->notify('init');
    return $this;
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
    $this->isLoaded = TRUE;
    $this->isNew = FALSE;
    $this->notify('init');
    $this->replaceData($data);
    $this->notify('load');
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
    if (!$this->isLoaded) {
      $this->load();
    }
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
    if (!$this->isLoaded) {
      $this->load();
    }
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
    // A load would destroy the data just set (for example on import).
    $this->isLoaded = TRUE;
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
   * Sets overridden data for this configuration object.
   *
   * The overridden data only applies to this configuration object.
   *
   * @param array $data
   *   The overridden values of the configuration data.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function setOverride(array $data) {
    $this->context->setOverrides($this->getName(), $data);
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Sets the current data for this configuration object.
   *
   * Merges overridden configuration data into the original data.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  protected function setOverriddenData() {
    $this->overriddenData = $this->data;
    $overrides = $this->context->getOverrides($this->getName());
    if (is_array($overrides)) {
      $this->overriddenData = NestedArray::mergeDeepArray(array($this->overriddenData, $overrides), TRUE);
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
    if (!$this->isLoaded) {
      $this->load();
    }

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
    if (!$this->isLoaded) {
      $this->load();
    }
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
   * Loads configuration data into this object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function load() {
    $this->isLoaded = FALSE;
    $data = $this->storage->read($this->name);
    if ($data === FALSE) {
      $this->isNew = TRUE;
      $this->replaceData(array());
    }
    else {
      $this->isNew = FALSE;
      $this->replaceData($data);
    }
    $this->notify('load');
    $this->isLoaded = TRUE;
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

    if (!$this->isLoaded) {
      $this->load();
    }
    $this->storage->write($this->name, $this->data);
    $this->isNew = FALSE;
    $this->notify('save');
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
    $this->notify('delete');
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
   * Dispatches a configuration event.
   *
   * @param string $config_event_name
   *   The configuration event name.
   */
  protected function notify($config_event_name) {
    $this->context->notify($config_event_name, $this);
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
    if (!$this->isLoaded) {
      $this->load();
    }
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
        // @todo throw an exception due to an incomplete schema. Only possible
        //   once https://drupal.org/node/1910624 is complete.
      }
    }
    else {
      // Any non-scalar value must be an array.
      if (!is_array($value)) {
        $value = (array) $value;
      }
      // Recurse into any nested keys.
      foreach ($value as $nested_value_key => $nested_value) {
        $value[$nested_value_key] = $this->castValue($key . '.' . $nested_value_key, $nested_value);
      }
    }
    return $value;
  }

}

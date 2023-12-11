<?php

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Schema\Ignore;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\Schema\Sequence;
use Drupal\Core\Config\Schema\SequenceDataDefinition;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\Core\Config\Schema\Undefined;

/**
 * Provides a base class for configuration objects with storage support.
 *
 * Encapsulates all capabilities needed for configuration handling for a
 * specific configuration object, including storage and data type casting.
 *
 * The default implementation in \Drupal\Core\Config\Config adds support for
 * runtime overrides. Extend from StorableConfigBase directly to manage
 * configuration with a storage backend that does not support overrides.
 *
 * @see \Drupal\Core\Config\Config
 */
abstract class StorableConfigBase extends ConfigBase {

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
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

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
  protected $originalData = [];

  /**
   * Saves the configuration object.
   *
   * Must invalidate the cache tags associated with the configuration object.
   *
   * @param bool $has_trusted_data
   *   Set to TRUE if the configuration data has already been checked to ensure
   *   it conforms to schema. Generally this is only used during module and
   *   theme installation.
   *
   * @return $this
   *
   * @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
   */
  abstract public function save($has_trusted_data = FALSE);

  /**
   * Deletes the configuration object.
   *
   * Must invalidate the cache tags associated with the configuration object.
   *
   * @return $this
   */
  abstract public function delete();

  /**
   * Initializes a configuration object with pre-loaded data.
   *
   * @param array $data
   *   Array of loaded data for this configuration object.
   *
   * @return $this
   *   The configuration object.
   */
  public function initWithData(array $data) {
    $this->isNew = FALSE;
    $this->data = $data;
    $this->originalData = $this->data;
    return $this;
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
   * Retrieves the storage used to load and save this configuration object.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage object.
   */
  public function getStorage() {
    return $this->storage;
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
   *
   * @return mixed
   *   The data that was requested.
   */
  public function getOriginal($key = '') {
    $original_data = $this->originalData;

    if (empty($key)) {
      return $original_data;
    }

    $parts = explode('.', $key);
    if (count($parts) == 1) {
      return $original_data[$key] ?? NULL;
    }

    $value = NestedArray::getValue($original_data, $parts, $key_exists);
    return $key_exists ? $value : NULL;
  }

  /**
   * Gets the raw data without any manipulations.
   *
   * @return array
   *   The raw data.
   */
  public function getRawData() {
    return $this->data;
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
      $this->schemaWrapper = $this->typedConfigManager->createFromNameAndData($this->name, $this->data);
    }
    return $this->schemaWrapper;
  }

  /**
   * Validate the values are allowed data types.
   *
   * @param string $key
   *   A string that maps to a key within the configuration data.
   * @param mixed $value
   *   Value to associate with the key.
   *
   * @throws \Drupal\Core\Config\UnsupportedDataTypeConfigException
   *   If the value is unsupported in configuration.
   */
  protected function validateValue($key, $value) {
    // Minimal validation. Should not try to serialize resources or non-arrays.
    if (is_array($value)) {
      foreach ($value as $nested_value_key => $nested_value) {
        $this->validateValue($key . '.' . $nested_value_key, $nested_value);
      }
    }
    elseif ($value !== NULL && !is_scalar($value)) {
      throw new UnsupportedDataTypeConfigException("Invalid data type for config element {$this->getName()}:$key");
    }
  }

  /**
   * Casts the value to correct data type using the configuration schema.
   *
   * @param string|null $key
   *   A string that maps to a key within the configuration data. If NULL the
   *   top level mapping will be processed.
   * @param mixed $value
   *   Value to associate with the key.
   *
   * @return mixed
   *   The value cast to the type indicated in the schema.
   *
   * @throws \Drupal\Core\Config\UnsupportedDataTypeConfigException
   *   If the value is unsupported in configuration.
   */
  protected function castValue($key, $value) {
    $element = $this->getSchemaWrapper();
    if ($key !== NULL) {
      $element = $element->get($key);
    }

    // Do not cast value if it is unknown or defined to be ignored.
    if ($element && ($element instanceof Undefined || $element instanceof Ignore)) {
      // Do validate the value (may throw UnsupportedDataTypeConfigException)
      // to ensure unsupported types are not supported in this case either.
      $this->validateValue($key, $value);
      return $value;
    }
    if (is_scalar($value) || $value === NULL) {
      if ($element && $element instanceof PrimitiveInterface) {
        // Special handling for integers and floats since the configuration
        // system is primarily concerned with saving values from the Form API
        // we have to special case the meaning of an empty string for numeric
        // types. In PHP this would be casted to a 0 but for the purposes of
        // configuration we need to treat this as a NULL.
        $empty_value = $value === '' && ($element instanceof IntegerInterface || $element instanceof FloatInterface);

        if ($value === NULL || $empty_value) {
          $value = NULL;
        }
        else {
          $value = $element->getCastedValue();
        }
      }
    }
    else {
      // Throw exception on any non-scalar or non-array value.
      if (!is_array($value)) {
        throw new UnsupportedDataTypeConfigException("Invalid data type for config element {$this->getName()}:$key");
      }
      // Recurse into any nested keys.
      foreach ($value as $nested_value_key => $nested_value) {
        $lookup_key = $key ? $key . '.' . $nested_value_key : $nested_value_key;
        $value[$nested_value_key] = $this->castValue($lookup_key, $nested_value);
      }

      // Only sort maps when we have more than 1 element to sort.
      if ($element instanceof Mapping && count($value) > 1) {
        $mapping = $element->getDataDefinition()['mapping'];
        if (is_array($mapping)) {
          // Only sort the keys in $value.
          $mapping = array_intersect_key($mapping, $value);
          // Sort the array in $value using the mapping definition.
          $value = array_replace($mapping, $value);
        }
      }

      if ($element instanceof Sequence) {
        $data_definition = $element->getDataDefinition();
        if ($data_definition instanceof SequenceDataDefinition) {
          // Apply any sorting defined on the schema.
          switch ($data_definition->getOrderBy()) {
            case 'key':
              ksort($value);
              break;

            case 'value':
              // The PHP documentation notes that "Be careful when sorting
              // arrays with mixed types values because sort() can produce
              // unpredictable results". There is no risk here because
              // \Drupal\Core\Config\StorableConfigBase::castValue() has
              // already cast all values to the same type using the
              // configuration schema.
              sort($value);
              break;

          }
        }
      }
    }
    return $value;
  }

}

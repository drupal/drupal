<?php

namespace Drupal\Core\Config\Schema;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;

/**
 * Provides a trait for checking configuration schema.
 */
trait SchemaCheckTrait {

  /**
   * The config schema wrapper object for the configuration object under test.
   *
   * @var \Drupal\Core\Config\Schema\Element
   */
  protected $schema;

  /**
   * The configuration object name under test.
   *
   * @var string
   */
  protected $configName;

  /**
   * Checks the TypedConfigManager has a valid schema for the configuration.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The TypedConfigManager.
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data, assumed to be data for a top-level config object.
   *
   * @return array|bool
   *   FALSE if no schema found. List of errors if any found. TRUE if fully
   *   valid.
   */
  public function checkConfigSchema(TypedConfigManagerInterface $typed_config, $config_name, $config_data) {
    // We'd like to verify that the top-level type is either config_base,
    // config_entity, or a derivative. The only thing we can really test though
    // is that the schema supports having langcode in it. So add 'langcode' to
    // the data if it doesn't already exist.
    if (!isset($config_data['langcode'])) {
      $config_data['langcode'] = 'en';
    }
    $this->configName = $config_name;
    if (!$typed_config->hasConfigSchema($config_name)) {
      return FALSE;
    }
    $this->schema = $typed_config->createFromNameAndData($config_name, $config_data);
    $errors = [];
    foreach ($config_data as $key => $value) {
      $errors[] = $this->checkValue($key, $value);
    }
    $errors = array_merge([], ...$errors);
    if (empty($errors)) {
      return TRUE;
    }
    return $errors;
  }

  /**
   * Helper method to check data type.
   *
   * @param string $key
   *   A string of configuration key.
   * @param mixed $value
   *   Value of given key.
   *
   * @return array
   *   List of errors found while checking with the corresponding schema.
   */
  protected function checkValue($key, $value) {
    $error_key = $this->configName . ':' . $key;
    /** @var \Drupal\Core\TypedData\TypedDataInterface $element */
    $element = $this->schema->get($key);

    // Check if this type has been deprecated.
    $data_definition = $element->getDataDefinition();
    if (!empty($data_definition['deprecated'])) {
      @trigger_error($data_definition['deprecated'], E_USER_DEPRECATED);
    }

    if ($element instanceof Undefined) {
      return [$error_key => 'missing schema'];
    }

    // Do not check value if it is defined to be ignored.
    if ($element && $element instanceof Ignore) {
      return [];
    }

    if ($element && is_scalar($value) || $value === NULL) {
      $success = FALSE;
      $type = gettype($value);
      if ($element instanceof PrimitiveInterface) {
        $success =
          ($type == 'integer' && $element instanceof IntegerInterface) ||
          // Allow integer values in a float field.
          (($type == 'double' || $type == 'integer') && $element instanceof FloatInterface) ||
          ($type == 'boolean' && $element instanceof BooleanInterface) ||
          ($type == 'string' && $element instanceof StringInterface) ||
          // Null values are allowed for all primitive types.
          ($value === NULL);
      }
      // Array elements can also opt-in for allowing a NULL value.
      elseif ($element instanceof ArrayElement && $element->isNullable() && $value === NULL) {
        $success = TRUE;
      }
      $class = get_class($element);
      if (!$success) {
        return [$error_key => "variable type is $type but applied schema class is $class"];
      }
    }
    else {
      $errors = [];
      if (!$element instanceof TraversableTypedDataInterface) {
        $errors[$error_key] = 'non-scalar value but not defined as an array (such as mapping or sequence)';
      }

      // Go on processing so we can get errors on all levels. Any non-scalar
      // value must be an array so cast to an array.
      if (!is_array($value)) {
        $value = (array) $value;
      }
      $nested_errors = [];
      // Recurse into any nested keys.
      foreach ($value as $nested_value_key => $nested_value) {
        $nested_errors[] = $this->checkValue($key . '.' . $nested_value_key, $nested_value);
      }
      return array_merge($errors, ...$nested_errors);
    }
    // No errors found.
    return [];
  }

}

<?php

/**
 * @file
 * Contains Drupal\config\Tests\ConfigSchemaTestBase.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Provides a base class to help test configuration schema.
 */
abstract class ConfigSchemaTestBase extends WebTestBase {

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
   * Global state for whether the config has a valid schema.
   *
   * @var boolean
   */
  protected $configPass;

  /**
   * Asserts the TypedConfigManager has a valid schema for the configuration.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The TypedConfigManager.
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data.
   */
  public function assertConfigSchema(TypedConfigManagerInterface $typed_config, $config_name, $config_data) {
    $this->configName = $config_name;
    if (!$typed_config->hasConfigSchema($config_name)) {
      $this->fail(String::format('No schema for !config_name', array('!config_name' => $config_name)));
      return;
    }
    $definition = $typed_config->getDefinition($config_name);
    $this->schema = $typed_config->create($definition, $config_data);
    $this->configPass = TRUE;
    foreach ($config_data as $key => $value) {
      $this->checkValue($key, $value);
    }
    if ($this->configPass) {
      $this->pass(String::format('Schema found for !config_name and values comply with schema.', array('!config_name' => $config_name)));
    }
  }

  /**
   * Helper method to check data type.
   *
   * @param string $key
   *   A string of configuration key.
   * @param mixed $value
   *   Value of given key.
   *
   * @return mixed
   *   Returns mixed value.
   */
  protected function checkValue($key, $value) {
    $element = FALSE;
    try {
      $element = $this->schema->get($key);
    }
    catch (SchemaIncompleteException $e) {
      if (is_scalar($value) || $value === NULL) {
        $this->fail("{$this->configName}:$key has no schema.");
      }
    }
    // Do not check value if it is defined to be ignored.
    if ($element && $element instanceof Ignore) {
      return $value;
    }

    if (is_scalar($value) || $value === NULL) {
      $success = FALSE;
      $type = gettype($value);
      if ($element instanceof PrimitiveInterface) {
        $success =
          ($type == 'integer' && $element instanceof IntegerInterface) ||
          ($type == 'double' && $element instanceof FloatInterface) ||
          ($type == 'boolean' && $element instanceof BooleanInterface) ||
          ($type == 'string' && $element instanceof StringInterface) ||
          // Null values are allowed for all types.
          ($value === NULL);
      }
      $class = get_class($element);
      if (!$success) {
        $this->fail("{$this->configName}:$key has the wrong schema. Variable type is $type and schema class is $class.");
      }
    }
    else {
      if (!$element instanceof ArrayElement) {
        $this->fail("Non-scalar {$this->configName}:$key is not defined as an array type (such as mapping or sequence).");
      }

      // Go on processing so we can get errors on all levels. Any non-scalar
      // value must be an array so cast to an array.
      if (!is_array($value)) {
        $value = (array) $value;
      }
      // Recurse into any nested keys.
      foreach ($value as $nested_value_key => $nested_value) {
        $value[$nested_value_key] = $this->checkValue($key . '.' . $nested_value_key, $nested_value);
      }
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function fail($message = NULL, $group = 'Other') {
    $this->configPass = FALSE;
    return parent::fail($message, $group);
  }

}

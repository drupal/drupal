<?php

/**
 * @file
 * Contains Drupal\config\Tests\DefaultConfigTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\Schema\Property;
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
   * @var boolean
   */
  protected $configPass;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Default configuration',
      'description' => 'Tests that default configuration provided by all modules matches schema.',
      'group' => 'Configuration',
    );
  }

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
    if (is_scalar($value) || $value === NULL) {
      try {
        $success = FALSE;
        $type = gettype($value);
        $element = $this->schema->get($key);
        if ($element instanceof PrimitiveInterface) {
          if ($type == 'integer' && $element instanceof IntegerInterface) {
            $success = TRUE;
          }
          if ($type == 'double' && $element instanceof FloatInterface) {
            $success = TRUE;
          }
          if ($type == 'boolean' && $element instanceof BooleanInterface) {
            $success = TRUE;
          }
          if ($type == 'string' && ($element instanceof StringInterface || $element instanceof Property)) {
            $success = TRUE;
          }
          // Null values are allowed for all types.
          if ($value === NULL) {
            $success = TRUE;
          }
        }
        else {
          // @todo throw an exception due to an incomplete schema. Only possible
          //   once https://drupal.org/node/1910624 is complete.
        }
        $class = get_class($element);
        if (!$success) {
          $this->fail("{$this->configName}:$key has the wrong schema. Variable type is $type and schema class is $class.");
        }
      }
      catch (SchemaIncompleteException $e) {
        $this->fail("{$this->configName}:$key has no schema.");
      }
    }
    else {
      // Any non-scalar value must be an array.
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

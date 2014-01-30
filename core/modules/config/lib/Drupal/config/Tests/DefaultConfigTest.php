<?php

/**
 * @file
 * Contains Drupal\config\Tests\DefaultConfigTest.
 */

namespace Drupal\config\Tests;

use Drupal\config_test\TestInstallStorage;
use Drupal\config_test\TestSchemaStorage;
use Drupal\Core\Config\Schema\Property;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Component\Utility\String;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\Type\FloatInterface;
use Drupal\Core\TypedData\Type\IntegerInterface;

/**
 * Tests default configuration availability and type with configuration schema.
 */
class DefaultConfigTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

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
   * Tests default configuration data type.
   */
  public function testDefaultConfig() {
    // Create a typed config manager with access to configuration schema in
    // every module, profile and theme.
    $typed_config = new TypedConfigManager(
      \Drupal::service('config.storage'),
      new TestSchemaStorage(),
      \Drupal::service('cache.config')
    );

    // Create a configuration storage with access to default configuration in
    // every module, profile and theme.
    $default_config_storage = new TestInstallStorage();

    foreach ($default_config_storage->listAll() as $config_name) {
      // @todo: remove once migration (https://drupal.org/node/2183957) and
      // translation (https://drupal.org/node/2168609) schemas are in.
      if (strpos($config_name, 'migrate.migration') === 0 || strpos($config_name, 'language.config') === 0) {
        continue;
      }

      // 1. config_test.noschema has to be skipped as it tests
      // TypedConfigManagerInterface::hasConfigSchema() method.
      // 2. config.someschema has to be skipped as it tests schema default data
      // type fallback.
      // 3. config_test.schema_in_install is testing that schema are used during
      // configuration installation.
      if ($config_name == 'config_test.noschema' || $config_name == 'config_test.someschema' || $config_name == 'config_test.schema_in_install') {
        continue;
      }

      $this->configName = $config_name;
      $data = $default_config_storage->read($config_name);
      if (!$typed_config->hasConfigSchema($config_name)) {
        $this->fail(String::format('No schema for !config_name', array('!config_name' => $config_name)));
        continue;
      }
      $definition = $typed_config->getDefinition($config_name);
      $this->schema = $typed_config->create($definition, $data);
      $this->configPass = TRUE;
      foreach ($data as $key => $value) {
        $this->checkValue($key, $value);
      }
      if ($this->configPass) {
        $this->pass(String::format('Schema found for !config_name and values comply with schema.', array('!config_name' => $config_name)));
      }
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

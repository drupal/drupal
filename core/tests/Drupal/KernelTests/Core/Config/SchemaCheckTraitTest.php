<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the functionality of SchemaCheckTrait.
 *
 * @group config
 */
class SchemaCheckTraitTest extends KernelTestBase {

  use SchemaCheckTrait;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['config_test', 'config_schema_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['config_test', 'config_schema_test']);
    $this->typedConfig = \Drupal::service('config.typed');
  }

  /**
   * Tests \Drupal\Core\Config\Schema\SchemaCheckTrait.
   *
   * @dataProvider providerCheckConfigSchema
   */
  public function testCheckConfigSchema(bool $validate_constraints, array $expectations) {
    // Test a non existing schema.
    $ret = $this->checkConfigSchema($this->typedConfig, 'config_schema_test.no_schema', $this->config('config_schema_test.no_schema')->get());
    $this->assertFalse($ret);

    // Test an existing schema with valid data.
    $config_data = $this->config('config_test.types')->get();
    $ret = $this->checkConfigSchema($this->typedConfig, 'config_test.types', $config_data);
    $this->assertTrue($ret);

    // Add a new key, a new array and overwrite boolean with array to test the
    // error messages.
    $config_data = ['new_key' => 'new_value', 'new_array' => []] + $config_data;
    $config_data['boolean'] = [];

    $ret = $this->checkConfigSchema($this->typedConfig, 'config_test.types', $config_data, $validate_constraints);
    $this->assertEquals($expectations, $ret);
  }

  public function providerCheckConfigSchema(): array {
    // Storage type check errors.
    // @see \Drupal\Core\Config\Schema\SchemaCheckTrait::checkValue()
    $expected_storage_type_check_errors = [
      'config_test.types:new_key' => 'missing schema',
      'config_test.types:new_array' => 'missing schema',
      'config_test.types:boolean' => 'non-scalar value but not defined as an array (such as mapping or sequence)',
    ];
    // Validation constraints violations.
    // @see \Drupal\Core\TypedData\TypedDataInterface::validate()
    $expected_validation_errors = [
      '0' => "[new_key] 'new_key' is not a supported key.",
      '1' => "[new_array] 'new_array' is not a supported key.",
      '2' => '[boolean] This value should be of the correct primitive type.',
    ];
    return [
      'without validation' => [
        FALSE,
        $expected_storage_type_check_errors,
      ],
      'with validation' => [
        TRUE,
        $expected_storage_type_check_errors + $expected_validation_errors,
      ],
    ];
  }

}

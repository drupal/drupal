<?php

declare(strict_types=1);

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
   * {@inheritdoc}
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
  public function testCheckConfigSchema(string $type_to_validate_against, bool $validate_constraints, array|bool $nulled_expectations, array|bool $no_data_expectations, array $expectations): void {
    // Test a non existing schema.
    $ret = $this->checkConfigSchema($this->typedConfig, 'config_schema_test.no_schema', $this->config('config_schema_test.no_schema')->get());
    $this->assertFalse($ret);

    // Test an existing schema with valid data.
    $config_data = $this->config('config_test.types')->get();
    $ret = $this->checkConfigSchema($this->typedConfig, 'config_test.types', $config_data);
    $this->assertTrue($ret);

    // Test it is possible to mark any schema type as required (not nullable).
    $nulled_config_data = array_fill_keys(array_keys($config_data), NULL);
    $ret = $this->checkConfigSchema($this->typedConfig, $type_to_validate_against, $nulled_config_data, $validate_constraints);
    $this->assertSame($nulled_expectations, $ret);

    // Add a new key, a new array and overwrite boolean with array to test the
    // error messages.
    $config_data = ['new_key' => 'new_value', 'new_array' => []] + $config_data;
    $config_data['boolean'] = [];

    $ret = $this->checkConfigSchema($this->typedConfig, $type_to_validate_against, $config_data, $validate_constraints);
    $this->assertEquals($expectations, $ret);

    // Omit all data, this should trigger validation errors for required keys
    // missing.
    $config_data = [];
    $ret = $this->checkConfigSchema($this->typedConfig, $type_to_validate_against, $config_data, $validate_constraints);
    $this->assertEquals($no_data_expectations, $ret);
  }

  /**
   * Returns test data for validating configuration schema.
   */
  public static function providerCheckConfigSchema(): array {
    // Storage type check errors.
    // @see \Drupal\Core\Config\Schema\SchemaCheckTrait::checkValue()
    $expected_storage_null_check_errors = [
      // TRICKY: `_core` is added during installation even if it is absent from
      // core/modules/config/tests/config_test/config/install/config_test.dynamic.dotted.default.yml.
      // @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
      'config_test.types:_core' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Mapping',
      'config_test.types:array' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Sequence',
      'config_test.types:mapping_with_only_required_keys' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Mapping',
      'config_test.types:mapping_with_some_required_keys' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Mapping',
      'config_test.types:mapping_with_only_optional_keys' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Mapping',
    ];
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
    $basic_cases = [
      'config_test.types, without validation' => [
        'config_test.types',
        FALSE,
        $expected_storage_null_check_errors,
        TRUE,
        $expected_storage_type_check_errors,
      ],
      'config_test.types, with validation' => [
        'config_test.types',
        TRUE,
        $expected_storage_null_check_errors,
        TRUE,
        $expected_storage_type_check_errors + $expected_validation_errors,
      ],
    ];

    // Test that if the exact same schema is reused but now has the constraint
    // "FullyValidatable" specified at the top level, that:
    // 1. `NULL` values now trigger validation errors, except when
    //    `nullable: true` is set.
    // 2. missing required keys now trigger validation errors, except when
    //    `requiredKey: false` is set.
    // @see `type: config_test.types.fully_validatable`
    // @see core/modules/config/tests/config_test/config/schema/config_test.schema.yml
    $expected_storage_null_check_errors = [
      // TRICKY: `_core` is added during installation even if it is absent from
      // core/modules/config/tests/config_test/config/install/config_test.dynamic.dotted.default.yml.
      // @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
      'config_test.types.fully_validatable:_core' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Mapping',
      'config_test.types.fully_validatable:array' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Sequence',
      'config_test.types.fully_validatable:mapping_with_only_required_keys' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Mapping',
      'config_test.types.fully_validatable:mapping_with_some_required_keys' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Mapping',
      'config_test.types.fully_validatable:mapping_with_only_optional_keys' => 'variable type is NULL but applied schema class is Drupal\Core\Config\Schema\Mapping',
    ];
    $expected_storage_type_check_errors = [
      'config_test.types.fully_validatable:new_key' => 'missing schema',
      'config_test.types.fully_validatable:new_array' => 'missing schema',
      'config_test.types.fully_validatable:boolean' => 'non-scalar value but not defined as an array (such as mapping or sequence)',
    ];
    $opt_in_cases = [
      'config_test.types.fully_validatable, without validation' => [
        'config_test.types.fully_validatable',
        FALSE,
        $expected_storage_null_check_errors,
        TRUE,
        $expected_storage_type_check_errors,
      ],
      'config_test.types.fully_validatable, with validation' => [
        'config_test.types.fully_validatable',
        TRUE,
        $expected_storage_null_check_errors + [
          '[_core] This value should not be null.',
          '[array] This value should not be null.',
          '[boolean] This value should not be null.',
          '[exp] This value should not be null.',
          '[float] This value should not be null.',
          '[float_as_integer] This value should not be null.',
          '[hex] This value should not be null.',
          '[int] This value should not be null.',
          '[string] This value should not be null.',
          '[string_int] This value should not be null.',
          '[mapping_with_only_required_keys] This value should not be null.',
          '[mapping_with_some_required_keys] This value should not be null.',
          '[mapping_with_only_optional_keys] This value should not be null.',
        ],
        [
          "[] 'array' is a required key.",
          "[] 'boolean' is a required key.",
          "[] 'exp' is a required key.",
          "[] 'float' is a required key.",
          "[] 'float_as_integer' is a required key.",
          "[] 'hex' is a required key.",
          "[] 'int' is a required key.",
          "[] 'string' is a required key.",
          "[] 'string_int' is a required key.",
          "[] 'nullable_array' is a required key.",
          "[] 'nullable_boolean' is a required key.",
          "[] 'nullable_exp' is a required key.",
          "[] 'nullable_float' is a required key.",
          "[] 'nullable_float_as_integer' is a required key.",
          "[] 'nullable_hex' is a required key.",
          "[] 'nullable_int' is a required key.",
          "[] 'nullable_octal' is a required key.",
          "[] 'nullable_string' is a required key.",
          "[] 'nullable_string_int' is a required key.",
          "[] 'mapping_with_only_required_keys' is a required key.",
          "[] 'mapping_with_some_required_keys' is a required key.",
          "[] 'mapping_with_only_optional_keys' is a required key.",
        ],
        $expected_storage_type_check_errors + $expected_validation_errors + [
          // For `mapping_with_only_required_keys`: errors for all 4 keys.
          3 => "[mapping_with_only_required_keys] 'north' is a required key.",
          4 => "[mapping_with_only_required_keys] 'east' is a required key.",
          5 => "[mapping_with_only_required_keys] 'south' is a required key.",
          6 => "[mapping_with_only_required_keys] 'west' is a required key.",
          // For `mapping_with_some_required_keys`: errors for 2 required keys.
          7 => "[mapping_with_some_required_keys] 'north' is a required key.",
          8 => "[mapping_with_some_required_keys] 'south' is a required key.",
        ],
      ],
    ];

    return array_merge($basic_cases, $opt_in_cases);
  }

}

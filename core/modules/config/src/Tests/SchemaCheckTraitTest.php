<?php

/**
 * @file
 * Contains \Drupal\config\Tests\SchemaCheckTraitTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\simpletest\KernelTestBase;


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
  public static $modules = array('config_test', 'config_schema_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('config_test', 'config_schema_test'));
    $this->typedConfig = \Drupal::service('config.typed');
  }

  /**
   * Tests \Drupal\Core\Config\Schema\SchemaCheckTrait.
   */
  public function testTrait() {
    // Test a non existing schema.
    $ret = $this->checkConfigSchema($this->typedConfig, 'config_schema_test.noschema', $this->config('config_schema_test.noschema')->get());
    $this->assertIdentical($ret, FALSE);

    // Test an existing schema with valid data.
    $config_data = $this->config('config_test.types')->get();
    $ret = $this->checkConfigSchema($this->typedConfig, 'config_test.types', $config_data);
    $this->assertIdentical($ret, TRUE);

    // Add a new key, a new array and overwrite boolean with array to test the
    // error messages.
    $config_data = array('new_key' => 'new_value', 'new_array' => array()) + $config_data;
    $config_data['boolean'] = array();
    $ret = $this->checkConfigSchema($this->typedConfig, 'config_test.types', $config_data);
    $expected = array(
      'config_test.types:new_key' => 'missing schema',
      'config_test.types:new_array' => 'missing schema',
      'config_test.types:boolean' => 'non-scalar value but not defined as an array (such as mapping or sequence)',
    );
    $this->assertEqual($ret, $expected);
  }

}

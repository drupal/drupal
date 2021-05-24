<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated schema.inc functions.
 *
 * @group legacy
 * @group extension
 */
class SchemaDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'dblog'];

  /**
   * Tests deprecation of database schema API functions.
   */
  public function testDeprecatedInstallSchema() {
    $this->expectDeprecation('drupal_install_schema() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/2970993');
    drupal_install_schema('dblog');
    $table = 'watchdog';
    $this->expectDeprecation('drupal_get_module_schema() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. Testing classes could use \Drupal\TestTools\Extension\SchemaInspector for introspection. See https://www.drupal.org/node/2970993');
    $this->assertArrayHasKey($table, drupal_get_module_schema('dblog'));
    $this->assertTrue(\Drupal::database()->schema()->tableExists($table));
    $this->expectDeprecation('drupal_uninstall_schema() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/2970993');
    drupal_uninstall_schema('dblog');
  }

  /**
   * Tests deprecation of _drupal_schema_initialize() function.
   */
  public function testDeprecatedSchemaInitialize() {
    $module = 'dblog';
    $table = 'watchdog';
    $schema = [$table => []];
    $this->expectDeprecation('_drupal_schema_initialize() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. No direct replacement is provided. See https://www.drupal.org/node/2970993');
    _drupal_schema_initialize($schema, $module, FALSE);
    $this->assertEquals($module, $schema[$table]['module']);
    $this->assertEquals($table, $schema[$table]['name']);
  }

}

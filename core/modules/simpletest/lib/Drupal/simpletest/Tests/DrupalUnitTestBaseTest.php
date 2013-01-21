<?php

/**
 * @file
 * Contains Drupal\simpletest\Tests\DrupalUnitTestBaseTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests DrupalUnitTestBase functionality.
 */
class DrupalUnitTestBaseTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  public static function getInfo() {
    return array(
      'name' => 'DrupalUnitTestBase',
      'description' => 'Tests DrupalUnitTestBase functionality.',
      'group' => 'SimpleTest',
    );
  }

  /**
   * Tests expected behavior of setUp().
   */
  function testSetUp() {
    $module = 'entity_test';
    $table = 'entity_test';

    // Verify that specified $modules have been loaded.
    $this->assertTrue(function_exists('entity_test_permission'), "$module.module was loaded.");
    // Verify that there is a fixed module list.
    $this->assertIdentical(array_keys(drupal_container()->get('module_handler')->getModuleList()), array($module));
    $this->assertIdentical(module_implements('permission'), array($module));

    // Verify that no modules have been installed.
    $this->assertFalse(db_table_exists($table), "'$table' database table not found.");
  }

  /**
   * Tests expected load behavior of enableModules().
   */
  function testEnableModulesLoad() {
    $module = 'field_test';

    // Verify that the module does not exist yet.
    $this->assertFalse(module_exists($module), "$module module not found.");
    $list = array_keys(drupal_container()->get('module_handler')->getModuleList());
    $this->assertFalse(in_array($module, $list), "$module module not found in the extension handler's module list.");
    $list = module_implements('permission');
    $this->assertFalse(in_array($module, $list), "{$module}_permission() in module_implements() not found.");

    // Enable the module.
    $this->enableModules(array($module), FALSE);

    // Verify that the module exists.
    $this->assertTrue(module_exists($module), "$module module found.");
    $list = array_keys(drupal_container()->get('module_handler')->getModuleList());
    $this->assertTrue(in_array($module, $list), "$module module found in the extension handler's module list.");
    $list = module_implements('permission');
    $this->assertTrue(in_array($module, $list), "{$module}_permission() in module_implements() found.");
  }

  /**
   * Tests expected installation behavior of enableModules().
   */
  function testEnableModulesInstall() {
    $module = 'node';
    $table = 'node';

    // @todo Remove after configuration system conversion.
    $this->enableModules(array('system'), FALSE);
    $this->installSchema('system', 'variable');

    // Verify that the module does not exist yet.
    $this->assertFalse(module_exists($module), "$module module not found.");
    $list = array_keys(drupal_container()->get('module_handler')->getModuleList());
    $this->assertFalse(in_array($module, $list), "$module module not found in the extension handler's module list.");
    $list = module_implements('permission');
    $this->assertFalse(in_array($module, $list), "{$module}_permission() in module_implements() not found.");

    $this->assertFalse(db_table_exists($table), "'$table' database table not found.");
    $schema = drupal_get_schema($table);
    $this->assertFalse($schema, "'$table' table schema not found.");

    // Enable the module.
    $this->enableModules(array($module));

    // Verify that the enabled module exists.
    $this->assertTrue(module_exists($module), "$module module found.");
    $list = array_keys(drupal_container()->get('module_handler')->getModuleList());
    $this->assertTrue(in_array($module, $list), "$module module found in the extension handler's module list.");
    $list = module_implements('permission');
    $this->assertTrue(in_array($module, $list), "{$module}_permission() in module_implements() found.");

    $this->assertTrue(db_table_exists($table), "'$table' database table found.");
    $schema = drupal_get_schema($table);
    $this->assertTrue($schema, "'$table' table schema found.");
  }

  /**
   * Tests installing of multiple modules via enableModules().
   *
   * Regression test: Each passed module has to be enabled and installed on its
   * own, in the same way as module_enable() enables only one module after the
   * other.
   */
  function testEnableModulesInstallMultiple() {
    // Field retrieves entity type plugins, and EntityTypeManager calls into
    // hook_entity_info_alter(). If both modules would be first enabled together
    // instead of each on its own, then Node module's alter implementation
    // would be called and this simply blows up. To further complicate matters,
    // additionally install Comment module, whose entity bundles depend on node
    // types.
    $this->enableModules(array('field', 'node', 'comment'));
    $this->pass('Comment module was installed.');
  }

  /**
   * Tests installing modules via enableModules() with DepedencyInjection services.
   */
  function testEnableModulesInstallContainer() {
    // Install Node module.
    // @todo field_sql_storage and field should technically not be necessary
    //   for an entity query.
    $this->enableModules(array('field_sql_storage', 'field', 'node'));
    // Perform an entity query against node.
    $query = entity_query('node');
    // Disable node access checks, since User module is not enabled.
    $query->accessCheck(FALSE);
    $query->condition('nid', 1);
    $query->execute();
    $this->pass('Entity field query was executed.');
  }

  /**
   * Tests expected behavior of installSchema().
   */
  function testInstallSchema() {
    $module = 'entity_test';
    $table = 'entity_test';
    // Verify that we can install a table from the module schema.
    $this->installSchema($module, $table);
    $this->assertTrue(db_table_exists($table), "'$table' database table found.");

    // Verify that the schema is known to Schema API.
    $schema = drupal_get_schema();
    $this->assertTrue($schema[$table], "'$table' table found in schema.");
    $schema = drupal_get_schema($table);
    $this->assertTrue($schema, "'$table' table schema found.");

    // Verify that a table from a unknown module cannot be installed.
    $module = 'database_test';
    $table = 'test';
    try {
      $this->installSchema($module, $table);
      $this->fail('Exception for non-retrievable schema found.');
    }
    catch (\Exception $e) {
      $this->pass('Exception for non-retrievable schema found.');
    }
    $this->assertFalse(db_table_exists($table), "'$table' database table not found.");
    $schema = drupal_get_schema($table);
    $this->assertFalse($schema, "'$table' table schema not found.");

    // Verify that the same table can be installed after enabling the module.
    $this->enableModules(array($module), FALSE);
    $this->installSchema($module, $table);
    $this->assertTrue(db_table_exists($table), "'$table' database table found.");
    $schema = drupal_get_schema($table);
    $this->assertTrue($schema, "'$table' table schema found.");
  }

  /**
   * Tests that the fixed module list is retained after enabling and installing modules.
   */
  function testEnableModulesFixedList() {
    // entity_test is loaded via $modules; its entity type should exist.
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == entity_get_info('entity_test'));

    // Load some additional modules; entity_test should still exist.
    $this->enableModules(array('entity', 'field', 'field_sql_storage', 'text', 'entity_test'), FALSE);
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == entity_get_info('entity_test'));

    // Install some other modules; entity_test should still exist.
    module_enable(array('field', 'field_sql_storage', 'field_test'), FALSE);
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == entity_get_info('entity_test'));

    // Disable one of those modules; entity_test should still exist.
    module_disable(array('field_test'));
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == entity_get_info('entity_test'));

    // Set the weight of a module; entity_test should still exist.
    module_set_weight('entity', -1);
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == entity_get_info('entity_test'));

    // Reactivate the disabled module without enabling it.
    $this->enableModules(array('field_test'), FALSE);

    // Create a field and an instance.
    $display = entity_create('entity_display', array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'viewMode' => 'default',
    ));
    $field = array(
      'field_name' => 'test_field',
      'type' => 'test_field'
    );
    field_create_field($field);
    $instance = array(
      'field_name' => $field['field_name'],
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    );
    field_create_instance($instance);
  }

  /**
   * Tests that theme() works right after loading a module.
   */
  function testEnableModulesTheme() {
    $element = array(
      '#type' => 'container',
      '#markup' => 'Foo',
      '#attributes' => array(),
    );
    $this->enableModules(array('system'), FALSE);
    // theme() throws an exception if modules are not loaded yet.
    drupal_render($element);
  }

}

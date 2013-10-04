<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\InstallTest.
 */

namespace Drupal\system\Tests\Module;

use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for module installation.
 */
class InstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('module_test');

  public static function getInfo() {
    return array(
      'name' => 'Module installation',
      'description' => 'Tests the installation of modules.',
      'group' => 'Module',
    );
  }

  /**
   * Test that calls to drupal_write_record() work during module installation.
   *
   * This is a useful function to test because modules often use it to insert
   * initial data in their database tables when they are being installed or
   * enabled. Furthermore, drupal_write_record() relies on the module schema
   * information being available, so this also checks that the data from one of
   * the module's hook implementations, in particular hook_schema(), is
   * properly available during this time. Therefore, this test helps ensure
   * that modules are fully functional while Drupal is installing and enabling
   * them.
   */
  public function testDrupalWriteRecord() {
    // Check for data that was inserted using drupal_write_record() while the
    // 'module_test' module was being installed and enabled.
    $data = db_query("SELECT data FROM {module_test}")->fetchCol();
    $this->assertTrue(in_array('Data inserted in hook_install()', $data), 'Data inserted using drupal_write_record() in hook_install() is correctly saved.');
  }

  /**
   * Tests enabling User module once more.
   *
   * Regression: The installer might enable a module twice due to automatic
   * dependency resolution. A bug caused the stored weight for User module to
   * be an array.
   */
  public function testEnableUserTwice() {
    \Drupal::moduleHandler()->install(array('user'), FALSE);
    $this->assertIdentical(config('system.module')->get('enabled.user'), 0);
  }

  /**
   * Tests recorded schema versions of early installed modules in the installer.
   */
  public function testRequiredModuleSchemaVersions() {
    $version = drupal_get_installed_schema_version('system', TRUE);
    $this->assertTrue($version > 0, 'System module version is > 0.');
    $version = drupal_get_installed_schema_version('user', TRUE);
    $this->assertTrue($version > 0, 'User module version is > 0.');
  }

  /**
   * Tests that an exception is thrown when a module name is too long.
   */
  public function testModuleNameLength() {
    $module_name = 'invalid_module_name_over_the_maximum_allowed_character_length';
    $message = format_string('Exception thrown when enabling module %name with a name length over the allowed maximum', array('%name' => $module_name));
    try {
      $this->container->get('module_handler')->install(array($module_name));
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass($message);
    }

    // Since for the UI, the submit callback uses FALSE, test that too.
    $message = format_string('Exception thrown when enabling as if via the UI the module %name with a name length over the allowed maximum', array('%name' => $module_name));
    try {
      $this->container->get('module_handler')->install(array($module_name), FALSE);
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass($message);
    }
  }

}

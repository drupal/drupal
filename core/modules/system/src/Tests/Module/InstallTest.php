<?php

namespace Drupal\system\Tests\Module;

use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the installation of modules.
 *
 * @group Module
 */
class InstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('module_test');

  /**
   * Verify that drupal_get_schema() can be used during module installation.
   */
  public function testGetSchemaAtInstallTime() {
    // @see module_test_install()
    $value = db_query("SELECT data FROM {module_test}")->fetchField();
    $this->assertIdentical($value, 'varchar');
  }

  /**
   * Tests enabling User module once more.
   *
   * Regression: The installer might enable a module twice due to automatic
   * dependency resolution. A bug caused the stored weight for User module to
   * be an array.
   */
  public function testEnableUserTwice() {
    \Drupal::service('module_installer')->install(array('user'), FALSE);
    $this->assertIdentical($this->config('core.extension')->get('module.user'), 0);
  }

  /**
   * Tests recorded schema versions of early installed modules in the installer.
   */
  public function testRequiredModuleSchemaVersions() {
    $version = drupal_get_installed_schema_version('system', TRUE);
    $this->assertTrue($version > 0, 'System module version is > 0.');
    $version = drupal_get_installed_schema_version('user', TRUE);
    $this->assertTrue($version > 0, 'User module version is > 0.');

    $post_update_key_value = \Drupal::keyValue('post_update');
    $existing_updates = $post_update_key_value->get('existing_updates', []);
    $this->assertTrue(in_array('module_test_post_update_test', $existing_updates));
  }

  /**
   * Ensures that post update functions are removed on uninstall.
   */
  public function testUninstallPostUpdateFunctions() {
    \Drupal::service('module_installer')->uninstall(['module_test']);

    $post_update_key_value = \Drupal::keyValue('post_update');
    $existing_updates = $post_update_key_value->get('existing_updates', []);
    $this->assertFalse(in_array('module_test_post_update_test', $existing_updates));
  }

  /**
   * Tests that an exception is thrown when a module name is too long.
   */
  public function testModuleNameLength() {
    $module_name = 'invalid_module_name_over_the_maximum_allowed_character_length';
    $message = format_string('Exception thrown when enabling module %name with a name length over the allowed maximum', array('%name' => $module_name));
    try {
      $this->container->get('module_installer')->install(array($module_name));
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass($message);
    }

    // Since for the UI, the submit callback uses FALSE, test that too.
    $message = format_string('Exception thrown when enabling as if via the UI the module %name with a name length over the allowed maximum', array('%name' => $module_name));
    try {
      $this->container->get('module_installer')->install(array($module_name), FALSE);
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass($message);
    }
  }

}

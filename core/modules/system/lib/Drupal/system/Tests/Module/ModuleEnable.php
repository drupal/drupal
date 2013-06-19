<?php

/**
 * @file
 * Contains Drupal\system\Tests\Module\ModuleEnable.
 */

namespace Drupal\system\Tests\Module;

use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\simpletest\WebTestBase;

/**
 * Tests enabling modules.
 */
class ModuleEnable extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Module enable',
      'description' => 'Tests enabling modules.',
      'group' => 'Module',
    );
  }

  /**
   * Tests enabling User module once more.
   *
   * Regression: The installer might enable a module twice due to automatic
   * dependency resolution. A bug caused the stored weight for User module to
   * be an array.
   */
  function testEnableUserTwice() {
    module_enable(array('user'), FALSE);
    $this->assertIdentical(config('system.module')->get('enabled.user'), '0');
  }

  /**
   * Tests recorded schema versions of early installed modules in the installer.
   */
  function testRequiredModuleSchemaVersions() {
    $version = drupal_get_installed_schema_version('system', TRUE);
    $this->assertTrue($version > 0, 'System module version is > 0.');
    $version = drupal_get_installed_schema_version('user', TRUE);
    $this->assertTrue($version > 0, 'User module version is > 0.');
  }

  /**
   * Tests that an exception is thrown when a module name is too long.
   */
  function testModuleNameLength() {
    $module_name = 'invalid_module_name_over_the_maximum_allowed_character_length';
    $message = format_string('Exception thrown when enabling module %name with a name length over the allowed maximum', array('%name' => $module_name));
    try {
      $this->container->get('module_handler')->enable(array($module_name));
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass($message);
    }

    // Since for the UI, the submit callback uses FALSE, test that too.
    $message = format_string('Exception thrown when enabling as if via the UI the module %name with a name length over the allowed maximum', array('%name' => $module_name));
    try {
      $this->container->get('module_handler')->enable(array($module_name), FALSE);
      $this->fail($message);
    }
    catch (ExtensionNameLengthException $e) {
      $this->pass($message);
    }
  }

}

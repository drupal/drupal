<?php

/**
 * @file
 * Contains Drupal\system\Tests\Module\ModuleEnable.
 */

namespace Drupal\system\Tests\Module;

use Drupal\simpletest\WebTestBase;

/**
 * Tests module_enable().
 */
class ModuleEnable extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Module enable',
      'description' => 'Tests module_enable().',
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

}

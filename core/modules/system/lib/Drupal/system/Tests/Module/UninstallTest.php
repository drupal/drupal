<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\UninstallTest.
 */

namespace Drupal\system\Tests\Module;

use Drupal\simpletest\WebTestBase;

/**
 * Unit tests for module uninstallation and related hooks.
 */
class UninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('module_test', 'user');

  public static function getInfo() {
    return array(
      'name' => 'Module uninstallation',
      'description' => 'Tests the uninstallation of modules.',
      'group' => 'Module',
    );
  }

  /**
   * Tests the hook_modules_uninstalled() of the user module.
   */
  function testUserPermsUninstalled() {
    // Uninstalls the module_test module, so hook_modules_uninstalled()
    // is executed.
    module_disable(array('module_test'));
    module_uninstall(array('module_test'));

    // Are the perms defined by module_test removed from {role_permission}.
    $count = db_query("SELECT COUNT(rid) FROM {role_permission} WHERE permission = :perm", array(':perm' => 'module_test perm'))->fetchField();
    $this->assertEqual(0, $count, 'Permissions were all removed.');
  }
}

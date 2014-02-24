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
    module_uninstall(array('module_test'));

    // Are the perms defined by module_test removed?
    $this->assertFalse(user_roles(FALSE, 'module_test perm'), 'Permissions were all removed.');
  }

  /**
   * Tests the Uninstall page.
   */
  function testUninstallPage() {
    $account = $this->drupalCreateUser(array('administer modules'));
    $this->drupalLogin($account);
    $this->drupalGet('admin/modules/uninstall');
    $this->assertTitle(t('Uninstall') . ' | Drupal');
  }
}

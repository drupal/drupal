<?php

/**
 * @file
 * Definition of Drupal\views\Tests\User\AccessPermissionTest.
 */

namespace Drupal\views\Tests\User;

use Drupal\user\Plugin\views\access\Permission;

/**
 * Tests views perm access plugin.
 *
 * @see Drupal\user\Plugin\views\access\Permission
 */
class AccessPermissionTest extends AccessTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User: Access permission',
      'description' => 'Tests views permission access plugin.',
      'group' => 'Views Modules',
    );
  }


  /**
   * Tests perm access plugin.
   */
  function testAccessPerm() {
    $view = $this->createViewFromConfig('test_access_perm');

    $access_plugin = $view->display_handler->getPlugin('access');
    $this->assertTrue($access_plugin instanceof Permission, 'Make sure the right class got instantiated.');

    $this->assertTrue($view->display_handler->access($this->adminUser), t('Admin-Account should be able to access the view everytime'));
    $this->assertFalse($view->display_handler->access($this->webUser));
    $this->assertTrue($view->display_handler->access($this->normalUser));
  }

}

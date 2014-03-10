<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\AccessPermissionTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\user\Plugin\views\access\Permission;
use Drupal\views\Views;

/**
 * Tests views perm access plugin.
 *
 * @see \Drupal\user\Plugin\views\access\Permission
 */
class AccessPermissionTest extends AccessTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_access_perm');

  public static function getInfo() {
    return array(
      'name' => 'User: Access permission',
      'description' => 'Tests views permission access plugin.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests perm access plugin.
   */
  function testAccessPerm() {
    $view = Views::getView('test_access_perm');
    $view->setDisplay();

    $access_plugin = $view->display_handler->getPlugin('access');
    $this->assertTrue($access_plugin instanceof Permission, 'Make sure the right class got instantiated.');

    $this->assertTrue($view->display_handler->access($this->adminUser), 'Admin-Account should be able to access the view everytime');
    $this->assertFalse($view->display_handler->access($this->webUser));
    $this->assertTrue($view->display_handler->access($this->normalUser));
  }

}

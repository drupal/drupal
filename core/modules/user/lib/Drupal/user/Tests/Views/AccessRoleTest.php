<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\AccessRoleTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\user\Plugin\views\access\Role;

/**
 * Tests views role access plugin.
 *
 * @see \Drupal\user\Plugin\views\access\Role
 */
class AccessRoleTest extends AccessTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_access_role');

  public static function getInfo() {
    return array(
      'name' => 'User: Access role',
      'description' => 'Tests views role access plugin.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests role access plugin.
   */
  function testAccessRole() {
    $view = views_get_view('test_access_role');
    $view->setDisplay();

    $view->displayHandlers->get('default')->options['access']['options']['role'] = array(
      $this->normalRole => $this->normalRole,
    );

    $access_plugin = $view->display_handler->getPlugin('access');
    $this->assertTrue($access_plugin instanceof Role, 'Make sure the right class got instantiated.');

    $this->assertTrue($view->display_handler->access($this->adminUser), 'Admin-Account should be able to access the view everytime');
    $this->assertFalse($view->display_handler->access($this->webUser));
    $this->assertTrue($view->display_handler->access($this->normalUser));
  }

}

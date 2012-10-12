<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\AccessTest
 */

namespace Drupal\views\Tests\Plugin;

/**
 * Basic test for pluggable access.
 *
 * @todo It probably make sense to split the test up by one for role/perm/none
 *   and the two generic ones.
 */
class AccessTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Access',
      'description' => 'Tests pluggable access for views.',
      'group' => 'Views Plugins'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->admin_user = $this->drupalCreateUser(array('access all views'));
    $this->web_user = $this->drupalCreateUser();
    $this->web_role = current($this->web_user->roles);

    $this->normal_role = $this->drupalCreateRole(array());
    $this->normal_user = $this->drupalCreateUser(array('views_test_data test permission'));
    $this->normal_user->roles[$this->normal_role] = $this->normal_role;
    // @todo when all the plugin information is cached make a reset function and
    // call it here.
  }

  /**
   * Tests none access plugin.
   */
  function testAccessNone() {
    $view = $this->createViewFromConfig('test_access_none');

    $this->assertTrue($view->display_handler->access($this->admin_user), t('Admin-Account should be able to access the view everytime'));
    $this->assertTrue($view->display_handler->access($this->web_user));
    $this->assertTrue($view->display_handler->access($this->normal_user));
  }

  /**
   * @todo Test abstract access plugin.
   */

  /**
   * Tests static access check.
   *
   * @see Drupal\views_test\Plugin\views\access\StaticTest
   */
  function testStaticAccessPlugin() {
    $view = $this->createViewFromConfig('test_access_static');

    $access_plugin = $view->display_handler->getPlugin('access');

    $this->assertFalse($access_plugin->access($this->normal_user));

    $access_plugin->options['access'] = TRUE;
    $this->assertTrue($access_plugin->access($this->normal_user));

    // FALSE comes from hook_menu caching.
    $expected_hook_menu = array(
      'views_test_data_test_static_access_callback', array(FALSE)
    );
    $hook_menu = $view->executeHookMenu('page_1');
    $this->assertEqual($expected_hook_menu, $hook_menu['test_access_static']['access arguments'][0]);

    $expected_hook_menu = array(
      'views_test_data_test_static_access_callback', array(TRUE)
    );
    $this->assertTrue(views_access($expected_hook_menu));
  }

  /**
   * Tests dynamic access plugin.
   *
   * @see Drupal\views_test\Plugin\views\access\DyamicTest
   */
  function testDynamicAccessPlugin() {
    $view = $this->createViewFromConfig('test_access_dynamic');
    $argument1 = $this->randomName();
    $argument2 = $this->randomName();
    variable_set('test_dynamic_access_argument1', $argument1);
    variable_set('test_dynamic_access_argument2', $argument2);

    $access_plugin = $view->display_handler->getPlugin('access');

    $this->assertFalse($access_plugin->access($this->normal_user));

    $access_plugin->options['access'] = TRUE;
    $this->assertFalse($access_plugin->access($this->normal_user));

    $view->setArguments(array($argument1, $argument2));
    $this->assertTrue($access_plugin->access($this->normal_user));

    // FALSE comes from hook_menu caching.
    $expected_hook_menu = array(
      'views_test_data_test_dynamic_access_callback', array(FALSE, 1, 2)
    );
    $hook_menu = $view->executeHookMenu('page_1');
    $this->assertEqual($expected_hook_menu, $hook_menu['test_access_dynamic']['access arguments'][0]);

    $expected_hook_menu = array(
      'views_test_data_test_dynamic_access_callback', array(TRUE, 1, 2)
    );
    $this->assertTrue(views_access($expected_hook_menu, $argument1, $argument2));
  }

}

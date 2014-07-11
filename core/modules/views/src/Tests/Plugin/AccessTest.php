<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\AccessTest
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests pluggable access for views.
 *
 * @group views
 * @todo It probably make sense to split the test up by one for role/perm/none
 *   and the two generic ones.
 */
class AccessTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_access_none', 'test_access_static', 'test_access_dynamic');

  /**
   * Modules to enable.
   *
   * @return array
   */
  public static $modules = array('node');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    ViewTestData::createTestViews(get_class($this), array('views_test_data'));

    $this->admin_user = $this->drupalCreateUser(array('access all views'));
    $this->web_user = $this->drupalCreateUser();
    $roles = $this->web_user->getRoles();
    $this->web_role = $roles[0];

    $this->normal_role = $this->drupalCreateRole(array());
    $this->normal_user = $this->drupalCreateUser(array('views_test_data test permission'));
    $this->normal_user->addRole($this->normal_role);
    // @todo when all the plugin information is cached make a reset function and
    // call it here.
  }

  /**
   * Tests none access plugin.
   */
  function testAccessNone() {
    $view = Views::getView('test_access_none');
    $view->setDisplay();

    $this->assertTrue($view->display_handler->access($this->admin_user), 'Admin-Account should be able to access the view everytime');
    $this->assertTrue($view->display_handler->access($this->web_user));
    $this->assertTrue($view->display_handler->access($this->normal_user));
  }

  /**
   * @todo Test abstract access plugin.
   */

  /**
   * Tests static access check.
   *
   * @see \Drupal\views_test\Plugin\views\access\StaticTest
   */
  function testStaticAccessPlugin() {
    $view = Views::getView('test_access_static');
    $view->setDisplay();

    $access_plugin = $view->display_handler->getPlugin('access');

    $this->assertFalse($access_plugin->access($this->normal_user));
    $this->drupalGet('test_access_static');
    $this->assertResponse(403);

    $display = &$view->storage->getDisplay('default');
    $display['display_options']['access']['options']['access'] = TRUE;
    $access_plugin->options['access'] = TRUE;
    $view->save();
    // Saving a view will cause the router to be rebuilt when the kernel
    // termination event fires. Simulate that here.
    $this->container->get('router.builder')->rebuildIfNeeded();

    $this->assertTrue($access_plugin->access($this->normal_user));

    $this->drupalGet('test_access_static');
    $this->assertResponse(200);
  }

}

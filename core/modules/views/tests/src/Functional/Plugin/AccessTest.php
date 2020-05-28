<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests pluggable access for views.
 *
 * @group views
 * @todo It probably make sense to split the test up by one for role/perm/none
 *   and the two generic ones.
 */
class AccessTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_access_none', 'test_access_static', 'test_access_dynamic'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Web user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Normal user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();

    ViewTestData::createTestViews(get_class($this), ['views_test_data']);

    $this->webUser = $this->drupalCreateUser();

    $normal_role = $this->drupalCreateRole([]);
    $this->normalUser = $this->drupalCreateUser(['views_test_data test permission']);
    $this->normalUser->addRole($normal_role);
    // @todo when all the plugin information is cached make a reset function and
    // call it here.
  }

  /**
   * Tests none access plugin.
   */
  public function testAccessNone() {
    $view = Views::getView('test_access_none');
    $view->setDisplay();

    $this->assertTrue($view->display_handler->access($this->webUser));
    $this->assertTrue($view->display_handler->access($this->normalUser));
  }

  /**
   * @todo Test abstract access plugin.
   */

  /**
   * Tests static access check.
   *
   * @see \Drupal\views_test\Plugin\views\access\StaticTest
   */
  public function testStaticAccessPlugin() {
    $view = Views::getView('test_access_static');
    $view->setDisplay();

    $access_plugin = $view->display_handler->getPlugin('access');

    $this->assertFalse($access_plugin->access($this->normalUser));
    $this->drupalGet('test_access_static');
    $this->assertSession()->statusCodeEquals(403);

    $display = &$view->storage->getDisplay('default');
    $display['display_options']['access']['options']['access'] = TRUE;
    $access_plugin->options['access'] = TRUE;
    $view->save();
    // Saving a view will cause the router to be rebuilt when the kernel
    // termination event fires. Simulate that here.
    $this->container->get('router.builder')->rebuildIfNeeded();

    $this->assertTrue($access_plugin->access($this->normalUser));

    $this->drupalGet('test_access_static');
    $this->assertSession()->statusCodeEquals(200);
  }

}

<?php

declare(strict_types=1);

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
  public static $testViews = [
    'test_access_none',
    'test_access_static',
    'test_access_dynamic',
    'test_content_access_filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

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

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    ViewTestData::createTestViews(static::class, ['views_test_data']);

    $this->webUser = $this->drupalCreateUser();

    $normal_role = $this->drupalCreateRole([]);
    $this->normalUser = $this->drupalCreateUser([
      'views_test_data test permission',
    ]);
    $this->normalUser->addRole($normal_role);
    // @todo when all the plugin information is cached make a reset function and
    // call it here.
  }

  /**
   * Tests none access plugin.
   */
  public function testAccessNone(): void {
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
  public function testStaticAccessPlugin(): void {
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

  /**
   * Tests that node_access table is joined when hook_node_grants() is implemented.
   */
  public function testContentAccessFilter(): void {
    $view = Views::getView('test_content_access_filter');
    $view->setDisplay('page_1');

    $view->initQuery();
    $view->execute();
    /** @var \Drupal\Core\Database\Query\Select $main_query */
    $main_query = $view->build_info['query'];
    $tables = array_keys($main_query->getTables());
    $this->assertNotContains('node_access', $tables);

    // Enable node access test module to ensure that table is present again.
    \Drupal::service('module_installer')->install(['node_access_test']);
    node_access_rebuild();

    $view = Views::getView('test_content_access_filter');
    $view->setDisplay('page_1');
    $view->initQuery();
    $view->execute();
    /** @var \Drupal\Core\Database\Query\Select $main_query */
    $main_query = $view->build_info['query'];
    $tables = array_keys($main_query->getTables());
    $this->assertContains('node_access', $tables);
  }

}

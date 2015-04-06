<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\CacheWebTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;

/**
 * Tests pluggable caching for views via a web test.
 *
 * @group views
 * @see views_plugin_cache
 */
class CacheWebTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_display');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests the output caching on an actual page.
   */
  public function testCacheOutputOnPage() {
    $view = Views::getView('test_display');
    $view->storage->setStatus(TRUE);
    $view->setDisplay('page_1');
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'results_lifespan' => '3600',
        'output_lifespan' => '3600'
      )
    ));
    $view->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    $output_key = $view->getDisplay()->getPlugin('cache')->generateOutputKey();
    $this->assertFalse(\Drupal::cache('render')->get($output_key));

    $this->drupalGet('test-display');
    $this->assertResponse(200);
    $this->assertTrue(\Drupal::cache('render')->get($output_key));

    $this->drupalGet('test-display');
    $this->assertResponse(200);
    $this->assertTrue(\Drupal::cache('render')->get($output_key));
  }

}

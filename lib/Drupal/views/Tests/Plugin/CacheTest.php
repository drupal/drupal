<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\CacheTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\View;

/**
 * Basic test for pluggable caching.
 *
 * @see views_plugin_cache
 */
class CacheTest extends PluginTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Cache',
      'description' => 'Tests pluggable caching for views.',
      'group' => 'Views Plugins'
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Build and return a basic view of the views_test table.
   *
   * @return Drupal\views\View
   */
  protected function getBasicView() {
    // Create the basic view.
    $view = new View(array(), 'view');
    $view->name = 'test_view';
    $view->addDisplay('default');
    $view->base_table = 'views_test';

    // Set up the fields we need.
    $display = $view->newDisplay('default', 'Master', 'default');
    $display->overrideOption('fields', array(
      'id' => array(
        'id' => 'id',
        'table' => 'views_test',
        'field' => 'id',
        'relationship' => 'none',
      ),
      'name' => array(
        'id' => 'name',
        'table' => 'views_test',
        'field' => 'name',
        'relationship' => 'none',
      ),
      'age' => array(
        'id' => 'age',
        'table' => 'views_test',
        'field' => 'age',
        'relationship' => 'none',
      ),
    ));

    // Set up the sort order.
    $display->overrideOption('sorts', array(
      'id' => array(
        'order' => 'ASC',
        'id' => 'id',
        'table' => 'views_test',
        'field' => 'id',
        'relationship' => 'none',
      ),
    ));

    return $view;
  }

  /**
   * Tests time based caching.
   *
   * @see views_plugin_cache_time
   */
  function testTimeCaching() {
    // Create a basic result which just 2 results.
    $view = $this->getView();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'results_lifespan' => '3600',
      'output_lifespan' => '3600',
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), t('The number of returned rows match.'));

    // Add another man to the beatles.
    $record = array(
      'name' => 'Rod Davis',
      'age' => 29,
      'job' => 'Banjo',
    );
    drupal_write_record('views_test', $record);

    // The Result should be the same as before, because of the caching.
    $view = $this->getView();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'results_lifespan' => '3600',
      'output_lifespan' => '3600',
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), t('The number of returned rows match.'));
  }

  /**
   * Tests no caching.
   *
   * @see views_plugin_cache_time
   */
  function testNoneCaching() {
    // Create a basic result which just 2 results.
    $view = $this->getView();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'none',
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), t('The number of returned rows match.'));

    // Add another man to the beatles.
    $record = array(
      'name' => 'Rod Davis',
      'age' => 29,
      'job' => 'Banjo',
    );

    drupal_write_record('views_test', $record);

    // The Result changes, because the view is not cached.
    $view = $this->getView();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'none',
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(6, count($view->result), t('The number of returned rows match.'));
  }

  /**
   * Tests css/js storage and restoring mechanism.
   */
  function testHeaderStorage() {
    // Create a view with output caching enabled.
    // Some hook_views_pre_render in views_test.module adds the test css/js file.
    // so they should be added to the css/js storage.
    $view = $this->getView();
    $view->name = 'test_cache_header_storage';
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'output_lifespan' => '3600',
    ));

    $view->preview();
    $view->destroy();
    unset($view->pre_render_called);
    drupal_static_reset('drupal_add_css');
    drupal_static_reset('drupal_add_js');

    $view->initDisplay();
    $view->preview();
    $css = drupal_add_css();
    $css_path = drupal_get_path('module', 'views_test') . '/views_cache.test.css';
    $js_path = drupal_get_path('module', 'views_test') . '/views_cache.test.js';
    $js = drupal_add_js();

    $this->assertTrue(isset($css[$css_path]), 'Make sure the css is added for cached views.');
    $this->assertTrue(isset($js[$js_path]), 'Make sure the js is added for cached views.');
    $this->assertFalse(!empty($view->pre_render_called), 'Make sure hook_views_pre_render is not called for the cached view.');
    $view->destroy();

    // Now add some css/jss before running the view.
    // Make sure that this css is not added when running the cached view.
    $view->name = 'test_cache_header_storage_2';

    $system_css_path = drupal_get_path('module', 'system') . '/system.maintenance.css';
    drupal_add_css($system_css_path);
    $system_js_path = drupal_get_path('module', 'system') . '/system.cron.js';
    drupal_add_js($system_js_path);

    $view->initDisplay();
    $view->preview();
    $view->destroy();
    drupal_static_reset('drupal_add_css');
    drupal_static_reset('drupal_add_js');

    $view->initDisplay();
    $view->preview();

    $css = drupal_add_css();
    $js = drupal_add_js();

    $this->assertFalse(isset($css[$system_css_path]), 'Make sure that unrelated css is not added.');
    $this->assertFalse(isset($js[$system_js_path]), 'Make sure that unrelated js is not added.');

  }

}

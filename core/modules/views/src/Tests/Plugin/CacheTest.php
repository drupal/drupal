<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\CacheTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * Tests pluggable caching for views.
 *
 * @group views
 * @see views_plugin_cache
 */
class CacheTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view', 'test_cache', 'test_groupwise_term_ui');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests time based caching.
   *
   * @see views_plugin_cache_time
   */
  public function testTimeCaching() {
    // Create a basic result which just 2 results.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'results_lifespan' => '3600',
        'output_lifespan' => '3600'
      )
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');

    // Add another man to the beatles.
    $record = array(
      'name' => 'Rod Davis',
      'age' => 29,
      'job' => 'Banjo',
    );
    db_insert('views_test_data')->fields($record)->execute();

    // The Result should be the same as before, because of the caching.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'results_lifespan' => '3600',
        'output_lifespan' => '3600'
      )
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');
  }

  /**
   * Tests no caching.
   *
   * @see views_plugin_cache_time
   */
  function testNoneCaching() {
    // Create a basic result which just 2 results.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'none',
      'options' => array()
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');

    // Add another man to the beatles.
    $record = array(
      'name' => 'Rod Davis',
      'age' => 29,
      'job' => 'Banjo',
    );
    db_insert('views_test_data')->fields($record)->execute();

    // The Result changes, because the view is not cached.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'none',
      'options' => array()
    ));

    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(6, count($view->result), 'The number of returned rows match.');
  }

  /**
   * Tests css/js storage and restoring mechanism.
   */
  function testHeaderStorage() {
    // Create a view with output caching enabled.
    // Some hook_views_pre_render in views_test_data.module adds the test css/js file.
    // so they should be added to the css/js storage.
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->storage->set('id', 'test_cache_header_storage');
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'output_lifespan' => '3600'
      )
    ));

    $output = $view->preview();
    drupal_render($output);
    unset($view->pre_render_called);
    $view->destroy();

    $view->setDisplay();
    $output = $view->preview();
    drupal_render($output);
    $this->assertTrue(in_array('views_test_data/test', $output['#attached']['library']), 'Make sure libraries are added for cached views.');
    $css_path = drupal_get_path('module', 'views_test_data') . '/views_cache.test.css';
    $js_path = drupal_get_path('module', 'views_test_data') . '/views_cache.test.js';
    $this->assertTrue(in_array($css_path, $output['#attached']['css']), 'Make sure the css is added for cached views.');
    $this->assertTrue(in_array($js_path, $output['#attached']['js']), 'Make sure the js is added for cached views.');
    $this->assertTrue(['views_test_data:1'], $output['#cache']['tags']);
    $this->assertTrue(['views_test_data_post_render_cache' => [['foo' => 'bar']]], $output['#post_render_cache']);
    $this->assertFalse(!empty($view->build_info['pre_render_called']), 'Make sure hook_views_pre_render is not called for the cached view.');
  }

  /**
   * Tests that Subqueries are cached as expected.
   */
  public function testSubqueryStringCache() {
    // Execute the view.
    $view = Views::getView('test_groupwise_term_ui');
    $view->setDisplay();
    $this->executeView($view);
    // Request for the cache.
    $cid = 'views_relationship_groupwise_max:test_groupwise_term_ui:default:tid_representative';
    $cache = \Drupal::cache('data')->get($cid);
    $this->assertEqual($cid, $cache->cid, 'Subquery String cached as expected.');

  }

}

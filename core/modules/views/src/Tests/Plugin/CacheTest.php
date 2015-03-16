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
  public static $testViews = array('test_view', 'test_cache', 'test_groupwise_term_ui', 'test_display');

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
  public function testTimeResultCaching() {
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'results_lifespan' => '3600',
        'output_lifespan' => '3600'
      )
    ));

    // Test the default (non-paged) display.
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

    // The result should be the same as before, because of the caching. (Note
    // that views_test_data records don't have associated cache tags, and hence
    // the results cache items aren't invalidated.)
    $view->destroy();
    $this->executeView($view);
    // Verify the result.
    $this->assertEqual(5, count($view->result), 'The number of returned rows match.');
  }

  /**
   * Tests result caching with a pager.
   */
  public function testTimeResultCachingWithPager() {
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'results_lifespan' => '3600',
        'output_lifespan' => '3600'
      )
    ));

    $mapping = ['views_test_data_name' => 'name'];

    $view->setDisplay('page_1');
    $view->setCurrentPage(0);
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [['name' => 'John'], ['name' => 'George']], $mapping);
    $view->destroy();

    $view->setDisplay('page_1');
    $view->setCurrentPage(1);
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [['name' => 'Ringo'], ['name' => 'Paul']], $mapping);
    $view->destroy();

    $view->setDisplay('page_1');
    $view->setCurrentPage(0);
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [['name' => 'John'], ['name' => 'George']], $mapping);
    $view->destroy();

    $view->setDisplay('page_1');
    $view->setCurrentPage(2);
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [['name' => 'Meredith']], $mapping);
    $view->destroy();
  }

  /**
   * Tests no caching.
   *
   * @see views_plugin_cache_time
   */
  function testNoneResultCaching() {
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
    $this->assertEqual(['foo' => 'bar'], $output['#attached']['drupalSettings'], 'Make sure drupalSettings are added for cached views.');
    // Note: views_test_data_views_pre_render() adds some cache tags.
    $this->assertEqual(['config:views.view.test_cache_header_storage', 'views_test_data:1'], $output['#cache']['tags']);
    $this->assertEqual(['views_test_data_post_render_cache' => [['foo' => 'bar']]], $output['#post_render_cache']);
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

  /**
   * Tests the data contained in cached items.
   */
  public function testCacheData() {
    for ($i = 1; $i <= 5; $i++) {
      $this->drupalCreateNode();
    }

    $view = Views::getView('test_display');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', array(
      'type' => 'time',
      'options' => array(
        'results_lifespan' => '3600',
        'output_lifespan' => '3600'
      )
    ));
    $this->executeView($view);

    // Get the cache item.
    $cid = $view->display_handler->getPlugin('cache')->generateResultsKey();
    $cache = \Drupal::cache('data')->get($cid);

    // Assert there are results, empty results would mean this test case would
    // pass otherwise.
    $this->assertTrue(count($cache->data['result']), 'Results saved in cached data.');

    // Assert each row doesn't contain '_entity' or '_relationship_entities'
    // items.
    foreach ($cache->data['result'] as $row) {
      $this->assertIdentical($row->_entity, NULL, 'Cached row "_entity" property is NULL');
      $this->assertIdentical($row->_relationship_entities, [], 'Cached row "_relationship_entities" property is empty');
    }
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

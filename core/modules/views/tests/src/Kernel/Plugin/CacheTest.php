<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Database\Database;
use Drupal\Core\Render\RenderContext;
use Drupal\node\Entity\Node;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\views_test_data\Plugin\views\filter\FilterTest as FilterPlugin;

/**
 * Tests pluggable caching for views.
 *
 * @group views
 * @see views_plugin_cache
 */
class CacheTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view', 'test_cache', 'test_groupwise_term_ui', 'test_display', 'test_filter'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy', 'text', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    // Setup the current time properly.
    \Drupal::request()->server->set('REQUEST_TIME', time());
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();

    $data['views_test_data']['test_cache_context'] = [
      'real field' => 'name',
      'title' => 'Test cache context',
      'filter' => [
        'id' => 'views_test_test_cache_context',
      ],
    ];

    return $data;
  }

  /**
   * Tests time based caching.
   *
   * @see views_plugin_cache_time
   */
  public function testTimeResultCaching() {
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'results_lifespan' => '3600',
        'output_lifespan' => '3600',
      ],
    ]);

    // Test the default (non-paged) display.
    $this->executeView($view);
    // Verify the result.
    $this->assertCount(5, $view->result, 'The number of returned rows match.');

    // Add another man to the beatles.
    $record = [
      'name' => 'Rod Davis',
      'age' => 29,
      'job' => 'Banjo',
    ];
    Database::getConnection()->insert('views_test_data')->fields($record)->execute();

    // The result should be the same as before, because of the caching. (Note
    // that views_test_data records don't have associated cache tags, and hence
    // the results cache items aren't invalidated.)
    $view->destroy();
    $this->executeView($view);
    // Verify the result.
    $this->assertCount(5, $view->result, 'The number of returned rows match.');
  }

  /**
   * Tests result caching with filters.
   *
   * @see views_plugin_cache_time
   */
  public function testTimeResultCachingWithFilter() {
    // Check that we can find the test filter plugin.
    $plugin = $this->container->get('plugin.manager.views.filter')->createInstance('test_filter');
    $this->assertInstanceOf(FilterPlugin::class, $plugin);

    $view = Views::getView('test_filter');
    $view->initDisplay();
    $view->display_handler->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'results_lifespan' => '3600',
        'output_lifespan' => '3600',
      ],
    ]);

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_filter' => [
        'id' => 'test_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '=',
        'value' => 'John',
        'group' => 0,
      ],
    ]);

    $this->executeView($view);

    // Get the cache item.
    $cid1 = $view->display_handler->getPlugin('cache')->generateResultsKey();

    // Build the expected result.
    $dataset = [['name' => 'John']];

    // Verify the result.
    $this->assertCount(1, $view->result, 'The number of returned rows match.');
    $this->assertIdenticalResultSet($view, $dataset, [
      'views_test_data_name' => 'name',
    ]);

    $view->destroy();

    $view->initDisplay();

    // Change the filtering.
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'test_filter' => [
        'id' => 'test_filter',
        'table' => 'views_test_data',
        'field' => 'name',
        'operator' => '=',
        'value' => 'Ringo',
        'group' => 0,
      ],
    ]);

    $this->executeView($view);

    // Get the cache item.
    $cid2 = $view->display_handler->getPlugin('cache')->generateResultsKey();
    $this->assertNotEquals($cid1, $cid2, "Results keys are different.");

    // Build the expected result.
    $dataset = [['name' => 'Ringo']];

    // Verify the result.
    $this->assertCount(1, $view->result, 'The number of returned rows match.');
    $this->assertIdenticalResultSet($view, $dataset, [
      'views_test_data_name' => 'name',
    ]);
  }

  /**
   * Tests result caching with a pager.
   */
  public function testTimeResultCachingWithPager() {
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'results_lifespan' => '3600',
        'output_lifespan' => '3600',
      ],
    ]);

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
  public function testNoneResultCaching() {
    // Create a basic result which just 2 results.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', [
      'type' => 'none',
      'options' => [],
    ]);

    $this->executeView($view);
    // Verify the result.
    $this->assertCount(5, $view->result, 'The number of returned rows match.');

    // Add another man to the beatles.
    $record = [
      'name' => 'Rod Davis',
      'age' => 29,
      'job' => 'Banjo',
    ];
    Database::getConnection()->insert('views_test_data')->fields($record)->execute();

    // The Result changes, because the view is not cached.
    $view = Views::getView('test_cache');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', [
      'type' => 'none',
      'options' => [],
    ]);

    $this->executeView($view);
    // Verify the result.
    $this->assertCount(6, $view->result, 'The number of returned rows match.');
  }

  /**
   * Tests css/js storage and restoring mechanism.
   */
  public function testHeaderStorage() {
    // Create a view with output caching enabled.
    // Some hook_views_pre_render in views_test_data.module adds the test css/js file.
    // so they should be added to the css/js storage.
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->storage->set('id', 'test_cache_header_storage');
    $view->display_handler->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'output_lifespan' => '3600',
      ],
    ]);

    $output = $view->buildRenderable();
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $renderer->executeInRenderContext(new RenderContext(), function () use (&$output, $renderer) {
      return $renderer->render($output);
    });

    unset($view->pre_render_called);
    $view->destroy();

    $view->setDisplay();
    $output = $view->buildRenderable();
    $renderer->executeInRenderContext(new RenderContext(), function () use (&$output, $renderer) {
      return $renderer->render($output);
    });

    $this->assertContains('views_test_data/test', $output['#attached']['library'], 'Make sure libraries are added for cached views.');
    $this->assertEquals(['foo' => 'bar'], $output['#attached']['drupalSettings'], 'Make sure drupalSettings are added for cached views.');
    // Note: views_test_data_views_pre_render() adds some cache tags.
    $this->assertEquals(['config:views.view.test_cache_header_storage', 'views_test_data:1'], $output['#cache']['tags']);
    $this->assertEquals(['non-existing-placeholder-just-for-testing-purposes' => ['#lazy_builder' => ['Drupal\views_test_data\Controller\ViewsTestDataController::placeholderLazyBuilder', ['bar']]]], $output['#attached']['placeholders']);
    $this->assertArrayNotHasKey('pre_render_called', $view->build_info, 'Make sure hook_views_pre_render is not called for the cached view.');
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
    $this->assertEquals($cid, $cache->cid, 'Subquery String cached as expected.');
  }

  /**
   * Tests the data contained in cached items.
   */
  public function testCacheData() {
    for ($i = 1; $i <= 5; $i++) {
      Node::create([
        'title' => $this->randomMachineName(8),
        'type' => 'page',
      ])->save();
    }

    $view = Views::getView('test_display');
    $view->setDisplay();
    $view->display_handler->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'results_lifespan' => '3600',
        'output_lifespan' => '3600',
      ],
    ]);
    $this->executeView($view);

    // Get the cache item.
    $cid = $view->display_handler->getPlugin('cache')->generateResultsKey();
    $cache = \Drupal::cache('data')->get($cid);

    // Assert there are results, empty results would mean this test case would
    // pass otherwise.
    $this->assertGreaterThan(0, count($cache->data['result']), 'Results saved in cached data.');

    // Assert each row doesn't contain '_entity' or '_relationship_entities'
    // items.
    foreach ($cache->data['result'] as $row) {
      $this->assertNull($row->_entity, 'Cached row "_entity" property is NULL');
      $this->assertSame([], $row->_relationship_entities, 'Cached row "_relationship_entities" property is empty');
    }
  }

  /**
   * Tests the cache context integration for views result cache.
   */
  public function testCacheContextIntegration() {
    $view = Views::getView('test_cache');
    $view->setDisplay('page_2');
    \Drupal::state()->set('views_test_cache_context', 'George');
    $this->executeView($view);

    $map = ['views_test_data_name' => 'name'];
    $this->assertIdenticalResultset($view, [['name' => 'George']], $map);

    // Update the entry in the DB to ensure that result caching works.
    \Drupal::database()->update('views_test_data')
      ->condition('name', 'George')
      ->fields(['name' => 'egroeG'])
      ->execute();

    $view = Views::getView('test_cache');
    $view->setDisplay('page_2');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [['name' => 'George']], $map);

    // Now change the cache context value, a different query should be executed.
    $view = Views::getView('test_cache');
    $view->setDisplay('page_2');
    \Drupal::state()->set('views_test_cache_context', 'Paul');
    $this->executeView($view);

    $this->assertIdenticalResultset($view, [['name' => 'Paul']], $map);
  }

  /**
   * Tests that cacheability metadata is carried over from argument defaults.
   */
  public function testArgumentDefaultCache() {
    $view = Views::getView('test_view');

    // Add a new argument and set the test plugin for the argument_default.
    $options = [
      'default_argument_type' => 'argument_default_test',
      'default_argument_options' => [
        'value' => 'John',
      ],
      'default_action' => 'default',
    ];
    $view->addHandler('default', 'argument', 'views_test_data', 'name', $options);
    $view->initHandlers();

    $output = $view->preview();

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $renderer->renderPlain($output);
    $this->assertEquals(['config:views.view.test_view', 'example_tag'], $output['#cache']['tags']);
  }

}

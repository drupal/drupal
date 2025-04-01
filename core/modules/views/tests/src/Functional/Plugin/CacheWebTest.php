<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Views;

/**
 * Tests pluggable caching for views via a web test.
 *
 * @group views
 * @see views_plugin_cache
 */
class CacheWebTest extends ViewTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * Tests the output caching on an actual page.
   */
  public function testCacheOutputOnPage(): void {
    $view = Views::getView('test_display');
    $view->storage->setStatus(TRUE);
    $view->setDisplay('page_1');
    $view->display_handler->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'results_lifespan' => '3600',
        'output_lifespan' => '3600',
      ],
    ]);
    $view->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    /** @var \Drupal\Core\Cache\VariationCacheFactoryInterface $vc_factory */
    $variation_cache_factory = \Drupal::service('variation_cache_factory');
    $variation_cache = $variation_cache_factory->get('render');

    /** @var \Drupal\Core\Render\RenderCacheInterface $render_cache */
    $render_cache = \Drupal::service('render_cache');
    $cache_element = DisplayPluginBase::buildBasicRenderable('test_display', 'page_1');
    $cache_element['#cache'] += ['contexts' => $this->container->getParameter('renderer.config')['required_cache_contexts']];
    $this->assertFalse($render_cache->get($cache_element));

    $this->drupalGet('test-display');
    $this->assertSession()->statusCodeEquals(200);

    // Because we warm caches in different requests, we do not properly populate
    // the internal properties of our variation cache. Reset it.
    $variation_cache->reset();

    $this->assertNotEmpty($render_cache->get($cache_element));
    $cache_tags = [
      'config:user.role.anonymous',
      'config:views.view.test_display',
      'node_list',
      'rendered',
    ];
    $this->assertCacheTags($cache_tags);

    $this->drupalGet('test-display');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNotEmpty($render_cache->get($cache_element));
    $this->assertCacheTags($cache_tags);
  }

  /**
   * Tests that a display without caching still contains the cache metadata.
   */
  public function testDisplayWithoutCacheStillBubblesMetadata(): void {
    $view = Views::getView('test_display');

    $uncached_block = $view->buildRenderable('block_1', [], FALSE);
    $cached_block = $view->buildRenderable('block_1', [], TRUE);
    $this->assertEquals($uncached_block['#cache']['contexts'], $cached_block['#cache']['contexts'], 'Cache contexts are the same when you render the view cached and uncached.');
  }

}

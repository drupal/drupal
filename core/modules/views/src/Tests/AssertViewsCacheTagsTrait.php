<?php

namespace Drupal\views\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;

trait AssertViewsCacheTagsTrait {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Asserts a view's result & render cache items' cache tags.
   *
   * This methods uses a full view object in order to render the view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to test, must have caching enabled.
   * @param null|string[] $expected_results_cache
   *   NULL when expecting no results cache item, a set of cache tags expected
   *   to be set on the results cache item otherwise.
   * @param bool $views_caching_is_enabled
   *   Whether to expect an output cache item. If TRUE, the cache tags must
   *   match those in $expected_render_array_cache_tags.
   * @param string[] $expected_render_array_cache_tags
   *   A set of cache tags expected to be set on the built view's render array.
   *
   * @return array
   *   The render array.
   */
  protected function assertViewsCacheTags(ViewExecutable $view, $expected_results_cache, $views_caching_is_enabled, array $expected_render_array_cache_tags) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    /** @var \Drupal\Core\Render\RenderCacheInterface $render_cache */
    $render_cache = \Drupal::service('render_cache');

    $build = $view->buildRenderable();
    $original = $build;

    // Ensure the current request is a GET request so that render caching is
    // active for direct rendering of views, just like for actual requests.
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = \Drupal::service('request_stack');
    $request = new Request();
    $request->server->set('REQUEST_TIME', REQUEST_TIME);
    $view->setRequest($request);
    $request_stack->push($request);
    $renderer->renderRoot($build);

    // Render array cache tags.
    $this->pass('Checking render array cache tags.');
    sort($expected_render_array_cache_tags);
    $this->assertEqual($build['#cache']['tags'], $expected_render_array_cache_tags);
    $this->debugCacheTags($build['#cache']['tags'], $expected_render_array_cache_tags);

    if ($views_caching_is_enabled) {
      $this->pass('Checking Views results cache item cache tags.');
      /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $cache_plugin */
      $cache_plugin = $view->display_handler->getPlugin('cache');

      // Results cache.

      // Ensure that the views query is built.
      $view->build();
      $results_cache_item = \Drupal::cache('data')->get($cache_plugin->generateResultsKey());
      if (is_array($expected_results_cache)) {
        $this->assertTrue($results_cache_item, 'Results cache item found.');
        if ($results_cache_item) {
          sort($expected_results_cache);
          $this->assertEqual($results_cache_item->tags, $expected_results_cache);
          $this->debugCacheTags($results_cache_item->tags, $expected_results_cache);
        }
      }
      else {
        $this->assertFalse($results_cache_item, 'Results cache item not found.');
      }

      $this->pass('Checking Views render cache item cache tags.');

      $original['#cache'] += ['contexts' => []];
      $original['#cache']['contexts'] = Cache::mergeContexts($original['#cache']['contexts'], $this->container->getParameter('renderer.config')['required_cache_contexts']);

      $render_cache_item = $render_cache->get($original);
      if ($views_caching_is_enabled === TRUE) {
        $this->assertTrue(!empty($render_cache_item), 'Render cache item found.');
        if ($render_cache_item) {
          $this->assertEqual($render_cache_item['#cache']['tags'], $expected_render_array_cache_tags);
          $this->debugCacheTags($render_cache_item['#cache']['tags'], $expected_render_array_cache_tags);
        }
      }
      else {
        $this->assertFalse($render_cache_item, 'Render cache item not found.');
      }
    }

    $view->destroy();

    $request_stack->pop();

    return $build;
  }

  /**
   * Asserts a view's result & render cache items' cache tags.
   *
   * This method starts with a pre bubbling basic render array.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param string[] $expected_render_array_cache_tags
   *   The expected render cache tags.
   * @param bool $views_caching_is_enabled
   *   Defines whether views output / render caching is enabled.
   *
   * @return array
   *   The render array.
   */
  protected function assertViewsCacheTagsFromStaticRenderArray(ViewExecutable $view, array $expected_render_array_cache_tags, $views_caching_is_enabled) {
    $original = $build = DisplayPluginBase::buildBasicRenderable($view->id(), $view->current_display ?: 'default', $view->args);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    /** @var \Drupal\Core\Render\RenderCacheInterface $render_cache */
    $render_cache = \Drupal::service('render_cache');

    // Ensure the current request is a GET request so that render caching is
    // active for direct rendering of views, just like for actual requests.
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = \Drupal::service('request_stack');
    $request = new Request();
    $request->server->set('REQUEST_TIME', REQUEST_TIME);
    $request_stack->push($request);
    $renderer->renderRoot($build);

    // Render array cache tags.
    $this->pass('Checking render array cache tags.');
    sort($expected_render_array_cache_tags);
    $this->assertEqual($build['#cache']['tags'], $expected_render_array_cache_tags);
    $this->debugCacheTags($build['#cache']['tags'], $expected_render_array_cache_tags);

    $this->pass('Checking Views render cache item cache tags.');
    $original['#cache'] += ['contexts' => []];
    $original['#cache']['contexts'] = Cache::mergeContexts($original['#cache']['contexts'], $this->container->getParameter('renderer.config')['required_cache_contexts']);

    $render_cache_item = $render_cache->get($original);
    if ($views_caching_is_enabled) {
      $this->assertTrue(!empty($render_cache_item), 'Render cache item found.');
      if ($render_cache_item) {
        $this->assertEqual($render_cache_item['#cache']['tags'], $expected_render_array_cache_tags);
        $this->debugCacheTags($render_cache_item['#cache']['tags'], $expected_render_array_cache_tags);
      }
    }
    else {
      $this->assertFalse($render_cache_item, 'Render cache item not found.');
    }

    return $build;
  }

}

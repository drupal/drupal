<?php

/**
 * @file
 * Contains \Drupal\views\Tests\AssertViewsCacheTagsTrait.
 */

namespace Drupal\views\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;

trait AssertViewsCacheTagsTrait {


  /**
   * Asserts a view's result & output cache items' cache tags.
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
   *   The render array
   */
  protected function assertViewsCacheTags(ViewExecutable $view, $expected_results_cache, $views_caching_is_enabled, array $expected_render_array_cache_tags) {
    $build = $view->preview();

    // Ensure the current request is a GET request so that render caching is
    // active for direct rendering of views, just like for actual requests.
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = \Drupal::service('request_stack');
    $request_stack->push(new Request());
    \Drupal::service('renderer')->renderRoot($build);
    $request_stack->pop();

    // Render array cache tags.
    $this->pass('Checking render array cache tags.');
    sort($expected_render_array_cache_tags);
    $this->assertEqual($build['#cache']['tags'], $expected_render_array_cache_tags);

    if ($views_caching_is_enabled) {
      $this->pass('Checking Views results cache item cache tags.');
      /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $cache_plugin */
      $cache_plugin = $view->display_handler->getPlugin('cache');

      // Results cache.
      $results_cache_item = \Drupal::cache('data')->get($cache_plugin->generateResultsKey());
      if (is_array($expected_results_cache)) {
        $this->assertTrue($results_cache_item, 'Results cache item found.');
        if ($results_cache_item) {
          sort($expected_results_cache);
          $this->assertEqual($results_cache_item->tags, $expected_results_cache);
        }
      }
      else {
        $this->assertFalse($results_cache_item, 'Results cache item not found.');
      }

      // Output cache.
      $this->pass('Checking Views output cache item cache tags.');
      $output_cache_item = \Drupal::cache('render')->get($cache_plugin->generateOutputKey());
      if ($views_caching_is_enabled === TRUE) {
        $this->assertTrue($output_cache_item, 'Output cache item found.');
        if ($output_cache_item) {
          $this->assertEqual($output_cache_item->tags, Cache::mergeTags($expected_render_array_cache_tags, ['rendered']));
        }
      }
      else {
        $this->assertFalse($output_cache_item, 'Output cache item not found.');
      }
    }

    $view->destroy();

    return $build;
  }

}

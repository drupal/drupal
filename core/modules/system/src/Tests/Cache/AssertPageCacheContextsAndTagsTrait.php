<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Url;

/**
 * Provides test assertions for testing page-level cache contexts & tags.
 *
 * Can be used by test classes that extend \Drupal\simpletest\WebTestBase.
 */
trait AssertPageCacheContextsAndTagsTrait {

  /**
   * Enables page caching.
   */
  protected function enablePageCaching() {
    $config = $this->config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();
  }

  /**
   * Asserts page cache miss, then hit for the given URL; checks cache headers.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to test.
   * @param string[] $expected_contexts
   *   The expected cache contexts for the given URL.
   * @param string[] $expected_tags
   *   The expected cache tags for the given URL.
   */
  protected function assertPageCacheContextsAndTags(Url $url, array $expected_contexts, array $expected_tags) {
    $absolute_url = $url->setAbsolute()->toString();
    sort($expected_contexts);
    sort($expected_tags);

    $get_cache_header_values = function ($header_name) {
      $header_value = $this->drupalGetHeader($header_name);
      if (empty($header_value)) {
        return [];
      }
      else {
        return explode(' ', $header_value);
      }
    };

    // Assert cache miss + expected cache contexts + tags.
    $this->drupalGet($absolute_url);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $actual_contexts = $get_cache_header_values('X-Drupal-Cache-Contexts');
    $actual_tags = $get_cache_header_values('X-Drupal-Cache-Tags');
    $this->assertIdentical($actual_contexts, $expected_contexts);
    if ($actual_contexts !== $expected_contexts) {
      debug(array_diff($actual_contexts, $expected_contexts));
    }
    $this->assertIdentical($actual_tags, $expected_tags);
    if ($actual_tags !== $expected_tags) {
      debug(array_diff($actual_tags, $expected_tags));
    }

    // Assert cache hit + expected cache contexts + tags.
    $this->drupalGet($absolute_url);
    $actual_contexts = $get_cache_header_values('X-Drupal-Cache-Contexts');
    $actual_tags = $get_cache_header_values('X-Drupal-Cache-Tags');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertIdentical($actual_contexts, $expected_contexts);
    if ($actual_contexts !== $expected_contexts) {
      debug(array_diff($actual_contexts, $expected_contexts));
    }
    $this->assertIdentical($actual_tags, $expected_tags);
    if ($actual_tags !== $expected_tags) {
      debug(array_diff($actual_tags, $expected_tags));
    }

    // Assert page cache item + expected cache tags.
    $cid_parts = array($url->setAbsolute()->toString(), 'html');
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('render')->get($cid);
    sort($cache_entry->tags);
    $this->assertEqual($cache_entry->tags, $expected_tags);
    if ($cache_entry->tags !== $expected_tags) {
      debug(array_diff($cache_entry->tags, $expected_tags));
    }
  }

}

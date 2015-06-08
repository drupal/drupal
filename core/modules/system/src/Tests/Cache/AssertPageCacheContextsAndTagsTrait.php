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
    $config->set('cache.page.max_age', 300);
    $config->save();
  }

  /**
   * Gets a specific header value as array.
   *
   * @param string $header_name
   *   The header name.
   *
   * @return string[]
   *   The header value, potentially exploded by spaces.
   */
  protected function getCacheHeaderValues($header_name) {
    $header_value = $this->drupalGetHeader($header_name);
    if (empty($header_value)) {
      return [];
    }
    else {
      return explode(' ', $header_value);
    }
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

    // Assert cache miss + expected cache contexts + tags.
    $this->drupalGet($absolute_url);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertCacheTags($expected_tags);
    $this->assertCacheContexts($expected_contexts);

    // Assert cache hit + expected cache contexts + tags.
    $this->drupalGet($absolute_url);
    $this->assertCacheTags($expected_tags);
    $this->assertCacheContexts($expected_contexts);

    // Assert page cache item + expected cache tags.
    $cid_parts = array($url->setAbsolute()->toString(), 'html');
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('render')->get($cid);
    sort($cache_entry->tags);
    $this->assertEqual($cache_entry->tags, $expected_tags);
    $this->debugCacheTags($cache_entry->tags, $expected_tags);
  }

  /**
   * Provides debug information for cache tags.
   *
   * @param string[] $actual_tags
   *   The actual cache tags.
   * @param string[] $expected_tags
   *   The expected cache tags.
   */
  protected function debugCacheTags(array $actual_tags, array $expected_tags) {
    if ($actual_tags !== $expected_tags) {
      debug('Missing cache tags: ' . implode(',', array_diff($expected_tags, $actual_tags)));
      debug('Unwanted cache tags: ' . implode(',', array_diff($actual_tags, $expected_tags)));
    }
  }

  /**
   * Ensures that some cache tags are present in the current response.
   *
   * @param string[] $expected_tags
   *   The expected tags.
   */
  protected function assertCacheTags(array $expected_tags) {
    $actual_tags = $this->getCacheHeaderValues('X-Drupal-Cache-Tags');
    sort($expected_tags);
    sort($actual_tags);
    $this->assertIdentical($actual_tags, $expected_tags);
    $this->debugCacheTags($actual_tags, $expected_tags);
  }

  /**
   * Ensures that some cache contexts are present in the current response.
   *
   * @param string[] $expected_contexts
   *   The expected cache contexts.
   * @param string $message
   *   (optional) A verbose message to output.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertCacheContexts(array $expected_contexts, $message = NULL) {
    $actual_contexts = $this->getCacheHeaderValues('X-Drupal-Cache-Contexts');
    sort($expected_contexts);
    sort($actual_contexts);
    $return = $this->assertIdentical($actual_contexts, $expected_contexts, $message);
    if (!$return) {
      debug('Missing cache contexts: ' . implode(',', array_diff($actual_contexts, $expected_contexts)));
      debug('Unwanted cache contexts: ' . implode(',', array_diff($expected_contexts, $actual_contexts)));
    }
    return $return;
  }

  /**
   * Asserts the max age header.
   *
   * @param int $max_age
   */
  protected function assertCacheMaxAge($max_age) {
    $cache_control_header = $this->drupalGetHeader('Cache-Control');
    if (strpos($cache_control_header, 'max-age:' . $max_age) === FALSE) {
      debug('Expected max-age:' . $max_age . '; Response max-age:' . $cache_control_header);
    }
    $this->assertTrue(strpos($cache_control_header, 'max-age:' . $max_age));
  }

}

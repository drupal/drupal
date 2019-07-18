<?php

namespace Drupal\Tests\system\Functional\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

/**
 * Provides test assertions for testing page-level cache contexts & tags.
 *
 * Can be used by test classes that extend \Drupal\Tests\BrowserTestBase.
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
   * Asserts whether an expected cache context was present in the last response.
   *
   * @param string $expected_cache_context
   *   The expected cache context.
   */
  protected function assertCacheContext($expected_cache_context) {
    $cache_contexts = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Contexts'));
    $this->assertTrue(in_array($expected_cache_context, $cache_contexts), "'" . $expected_cache_context . "' is present in the X-Drupal-Cache-Contexts header.");
  }

  /**
   * Asserts that a cache context was not present in the last response.
   *
   * @param string $not_expected_cache_context
   *   The expected cache context.
   */
  protected function assertNoCacheContext($not_expected_cache_context) {
    $cache_contexts = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Contexts'));
    $this->assertFalse(in_array($not_expected_cache_context, $cache_contexts), "'" . $not_expected_cache_context . "' is not present in the X-Drupal-Cache-Contexts header.");
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
    $cid_parts = [$url->setAbsolute()->toString(), ''];
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('page')->get($cid);
    sort($cache_entry->tags);
    $this->assertEqual($cache_entry->tags, $expected_tags);
  }

  /**
   * Ensures that some cache tags are present in the current response.
   *
   * @param string[] $expected_tags
   *   The expected tags.
   * @param bool $include_default_tags
   *   (optional) Whether the default cache tags should be included.
   */
  protected function assertCacheTags(array $expected_tags, $include_default_tags = TRUE) {
    // The anonymous role cache tag is only added if the user is anonymous.
    if ($include_default_tags) {
      if (\Drupal::currentUser()->isAnonymous()) {
        $expected_tags = Cache::mergeTags($expected_tags, ['config:user.role.anonymous']);
      }
      $expected_tags[] = 'http_response';
    }
    $actual_tags = $this->getCacheHeaderValues('X-Drupal-Cache-Tags');
    $expected_tags = array_unique($expected_tags);
    sort($expected_tags);
    sort($actual_tags);
    $this->assertIdentical($actual_tags, $expected_tags);
  }

  /**
   * Ensures that some cache contexts are present in the current response.
   *
   * @param string[] $expected_contexts
   *   The expected cache contexts.
   * @param string $message
   *   (optional) A verbose message to output.
   * @param bool $include_default_contexts
   *   (optional) Whether the default contexts should automatically be included.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertCacheContexts(array $expected_contexts, $message = NULL, $include_default_contexts = TRUE) {
    if ($include_default_contexts) {
      $default_contexts = ['languages:language_interface', 'theme'];
      // Add the user.permission context to the list of default contexts except
      // when user is already there.
      if (!in_array('user', $expected_contexts)) {
        $default_contexts[] = 'user.permissions';
      }
      $expected_contexts = Cache::mergeContexts($expected_contexts, $default_contexts);
    }

    $actual_contexts = $this->getCacheHeaderValues('X-Drupal-Cache-Contexts');
    sort($expected_contexts);
    sort($actual_contexts);
    $this->assertIdentical($actual_contexts, $expected_contexts, $message);
    return $actual_contexts === $expected_contexts;
  }

  /**
   * Asserts the max age header.
   *
   * @param int $max_age
   */
  protected function assertCacheMaxAge($max_age) {
    $cache_control_header = $this->drupalGetHeader('Cache-Control');
    $this->assertContains('max-age:' . $max_age, $cache_control_header);
  }

}

<?php

namespace Drupal\system\Tests\Cache;

@trigger_error(__NAMESPACE__ . '\PageCacheTagsTestBase is deprecated for removal before Drupal 9.0.0. Use \Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase instead. See https://www.drupal.org/node/2999939', E_USER_DEPRECATED);

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides helper methods for page cache tags tests.
 *
 * @deprecated in drupal:8.?.? and is removed from drupal:9.0.0.
 *   Use \Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase instead.
 *
 * @see https://www.drupal.org/node/2999939
 */
abstract class PageCacheTagsTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   *
   * Always enable header dumping in page cache tags tests, this aids debugging.
   */
  protected $dumpHeaders = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();
  }

  /**
   * Verify that when loading a given page, it's a page cache hit or miss.
   *
   * @param \Drupal\Core\Url $url
   *   The page for this URL will be loaded.
   * @param string $hit_or_miss
   *   'HIT' if a page cache hit is expected, 'MISS' otherwise.
   *
   * @param array|false $tags
   *   When expecting a page cache hit, you may optionally specify an array of
   *   expected cache tags. While FALSE, the cache tags will not be verified.
   */
  protected function verifyPageCache(Url $url, $hit_or_miss, $tags = FALSE) {
    $this->drupalGet($url);
    $message = new FormattableMarkup('Page cache @hit_or_miss for %path.', ['@hit_or_miss' => $hit_or_miss, '%path' => $url->toString()]);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), $hit_or_miss, $message);

    if ($hit_or_miss === 'HIT' && is_array($tags)) {
      $absolute_url = $url->setAbsolute()->toString();
      $cid_parts = [$absolute_url, 'html'];
      $cid = implode(':', $cid_parts);
      $cache_entry = \Drupal::cache('page')->get($cid);
      sort($cache_entry->tags);
      $tags = array_unique($tags);
      sort($tags);
      $this->assertIdentical($cache_entry->tags, $tags);
    }
  }

}

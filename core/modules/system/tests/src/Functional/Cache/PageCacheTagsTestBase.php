<?php

namespace Drupal\Tests\system\Functional\Cache;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides helper methods for page cache tags tests.
 */
abstract class PageCacheTagsTestBase extends BrowserTestBase {

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
   * @param array|false $tags
   *   When expecting a page cache hit, you may optionally specify an array of
   *   expected cache tags. While FALSE, the cache tags will not be verified.
   */
  protected function verifyPageCache(Url $url, $hit_or_miss, $tags = FALSE) {
    $this->drupalGet($url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', $hit_or_miss);

    if ($hit_or_miss === 'HIT' && is_array($tags)) {
      $absolute_url = $url->setAbsolute()->toString();
      $cid_parts = [$absolute_url, ''];
      $cid = implode(':', $cid_parts);
      $cache_entry = \Drupal::cache('page')->get($cid);
      sort($cache_entry->tags);
      $tags = array_unique($tags);
      sort($tags);
      $this->assertSame($cache_entry->tags, $tags);
    }
  }

  /**
   * Verify that when loading a given page, it's a page cache hit or miss.
   *
   * @param \Drupal\Core\Url $url
   *   The page for this URL will be loaded.
   * @param string $hit_or_miss
   *   'HIT' if a page cache hit is expected, 'MISS' otherwise.
   */
  protected function verifyDynamicPageCache(Url $url, $hit_or_miss) {
    $this->drupalGet($url);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', $hit_or_miss);
  }

}

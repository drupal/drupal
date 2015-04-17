<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\PageCacheTagsTestBase.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides helper methods for page cache tags tests.
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
   * @param array|FALSE $tags
   *   When expecting a page cache hit, you may optionally specify an array of
   *   expected cache tags. While FALSE, the cache tags will not be verified.
   */
  protected function verifyPageCache(Url $url, $hit_or_miss, $tags = FALSE) {
    $this->drupalGet($url);
    $message = SafeMarkup::format('Page cache @hit_or_miss for %path.', array('@hit_or_miss' => $hit_or_miss, '%path' => $url->toString()));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), $hit_or_miss, $message);

    if ($hit_or_miss === 'HIT' && is_array($tags)) {
      $absolute_url = $url->setAbsolute()->toString();
      $cid_parts = array($absolute_url, 'html');
      $cid = implode(':', $cid_parts);
      $cache_entry = \Drupal::cache('render')->get($cid);
      sort($cache_entry->tags);
      $tags = array_unique($tags);
      sort($tags);
      $this->assertIdentical($cache_entry->tags, $tags);
    }
  }

}

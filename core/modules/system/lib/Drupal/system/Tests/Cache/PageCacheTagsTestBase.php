<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\PageCacheTagsTestBase.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\String;

/**
 * Provides helper methods for page cache tags tests.
 */
abstract class PageCacheTagsTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Enable page caching.
    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 3600);
    $config->save();
  }

  /**
   * Verify that when loading a given page, it's a page cache hit or miss.
   *
   * @param string $path
   *   The page at this path will be loaded.
   * @param string $hit_or_miss
   *   'HIT' if a page cache hit is expected, 'MISS' otherwise.
   *
   * @param array|FALSE $tags
   *   When expecting a page cache hit, you may optionally specify an array of
   *   expected cache tags. While FALSE, the cache tags will not be verified.
   */
  protected function verifyPageCache($path, $hit_or_miss, $tags = FALSE) {
    $this->drupalGet($path);
    $message = String::format('Page cache @hit_or_miss for %path.', array('@hit_or_miss' => $hit_or_miss, '%path' => $path));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), $hit_or_miss, $message);

    if ($hit_or_miss === 'HIT' && is_array($tags)) {
      $cid_parts = array(url($path, array('absolute' => TRUE)), 'html');
      $cid = sha1(implode(':', $cid_parts));
      $cache_entry = \Drupal::cache('page')->get($cid);
      $this->assertIdentical($cache_entry->tags, $tags);
    }
  }

}

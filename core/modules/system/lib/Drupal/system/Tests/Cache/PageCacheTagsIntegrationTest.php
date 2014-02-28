<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\PageCacheTagsIntegrationTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Cache\Cache;

/**
 * Enables the page cache and tests its cache tags in various scenarios.
 *
 * @see \Drupal\system\Tests\Bootstrap\PageCacheTest
 * @see \Drupal\node\Tests\NodePageCacheTest
 * @see \Drupal\menu\Tests\MenuTest::testMenuBlockPageCacheTags()
 */
class PageCacheTagsIntegrationTest extends WebTestBase {

  protected $profile = 'standard';

  protected $dumpHeaders = TRUE;

  public static function getInfo() {
    return array(
      'name' => 'Page cache tags integration test',
      'description' => 'Enable the page cache and test its cache tags in various scenarios.',
      'group' => 'Cache',
    );
  }

  function setUp() {
    parent::setUp();

    $config = \Drupal::config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();
  }

  /**
   * Test that cache tags are properly bubbled up to the page level.
   */
  function testPageCacheTags() {
    // Create two nodes.
    $author_1 = $this->drupalCreateUser();
    $node_1 = $this->drupalCreateNode(array(
      'uid' => $author_1->id(),
      'title' => 'Node 1',
      'body' => array(
        0 => array('value' => 'Body 1', 'format' => 'basic_html'),
      ),
      'promote' => NODE_PROMOTED,
    ));
    $author_2 = $this->drupalCreateUser();
    $node_2 = $this->drupalCreateNode(array(
      'uid' => $author_2->id(),
      'title' => 'Node 2',
      'body' => array(
        0 => array('value' => 'Body 2', 'format' => 'full_html'),
      ),
      'promote' => NODE_PROMOTED,
    ));

    // Full node page 1.
    $this->verifyPageCacheTags('node/' . $node_1->id(), array(
      'content:1',
      'node_view:1',
      'node:' . $node_1->id(),
      'user:' . $author_1->id(),
      'filter_format:basic_html',
      'menu:footer',
      'menu:main',
    ));

    // Full node page 2.
    $this->verifyPageCacheTags('node/' . $node_2->id(), array(
      'content:1',
      'node_view:1',
      'node:' . $node_2->id(),
      'user:' . $author_2->id(),
      'filter_format:full_html',
      'menu:footer',
      'menu:main',
    ));
  }

  /**
   * Fills page cache for the given path, verify cache tags on page cache hit.
   *
   * @param $path
   *   The Drupal page path to test.
   * @param $expected_tags
   *   The expected cache tags for the page cache entry of the given $path.
   */
  protected function verifyPageCacheTags($path, $expected_tags) {
    $this->drupalGet($path);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->drupalGet($path);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $cid_parts = array(url($path, array('absolute' => TRUE)), 'html');
    $cid = sha1(implode(':', $cid_parts));
    $cache_entry = \Drupal::cache('page')->get($cid);
    $this->assertIdentical($cache_entry->tags, $expected_tags);
  }

}

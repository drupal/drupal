<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\PageCacheTagsIntegrationTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Cache\Cache;

/**
 * Enables the page cache and tests its cache tags in various scenarios.
 *
 * @group Cache
 * @see \Drupal\system\Tests\Bootstrap\PageCacheTest
 * @see \Drupal\node\Tests\NodePageCacheTest
 * @see \Drupal\menu_ui\Tests\MenuTest::testMenuBlockPageCacheTags()
 */
class PageCacheTagsIntegrationTest extends WebTestBase {

  protected $profile = 'standard';

  protected $dumpHeaders = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $config = $this->config('system.performance');
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

    // Place a block, but only make it visible on full node page 2.
    $block = $this->drupalPlaceBlock('views_block:comments_recent-block_1', array(
      'visibility' => array(
        'request_path' => array(
          'pages' => 'node/' . $node_2->id(),
        ),
      ),
    ));

    $cache_contexts = [
      'language',
      'menu.active_trail:account',
      'menu.active_trail:footer',
      'menu.active_trail:main',
      'menu.active_trail:tools',
      'theme',
      'timezone',
      'user.roles',
    ];

    // Full node page 1.
    $this->assertPageCacheContextsAndTags($node_1->urlInfo(), $cache_contexts, array(
      'rendered',
      'block_view',
      'config:block_list',
      'config:block.block.bartik_breadcrumbs',
      'config:block.block.bartik_content',
      'config:block.block.bartik_tools',
      'config:block.block.bartik_login',
      'config:block.block.bartik_footer',
      'config:block.block.bartik_powered',
      'config:block.block.bartik_main_menu',
      'config:block.block.bartik_account_menu',
      'node_view',
      'node:' . $node_1->id(),
      'user:' . $author_1->id(),
      'config:filter.format.basic_html',
      'config:system.menu.account',
      'config:system.menu.tools',
      'config:system.menu.footer',
      'config:system.menu.main',
      'config:system.site',
    ));

    // Full node page 2.
    $this->assertPageCacheContextsAndTags($node_2->urlInfo(), $cache_contexts, array(
      'rendered',
      'block_view',
      'config:block_list',
      'config:block.block.bartik_breadcrumbs',
      'config:block.block.bartik_content',
      'config:block.block.bartik_tools',
      'config:block.block.bartik_login',
      'config:block.block.' . $block->id(),
      'config:block.block.bartik_footer',
      'config:block.block.bartik_powered',
      'config:block.block.bartik_main_menu',
      'config:block.block.bartik_account_menu',
      'node_view',
      'node:' . $node_2->id(),
      'user:' . $author_2->id(),
      'config:filter.format.full_html',
      'config:system.menu.account',
      'config:system.menu.tools',
      'config:system.menu.footer',
      'config:system.menu.main',
      'config:system.site',
    ));
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
    $actual_contexts = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Contexts'));
    $actual_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertIdentical($actual_contexts, $expected_contexts);
    $this->assertIdentical($actual_tags, $expected_tags);

    // Assert cache hit + expected cache contexts + tags.
    $this->drupalGet($absolute_url);
    $actual_contexts = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Contexts'));
    $actual_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertIdentical($actual_contexts, $expected_contexts);
    $this->assertIdentical($actual_tags, $expected_tags);

    // Assert page cache item + expected cache tags.
    $cid_parts = array($url->setAbsolute()->toString(), 'html');
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('render')->get($cid);
    sort($cache_entry->tags);
    $this->assertEqual($cache_entry->tags, $expected_tags);
  }

}

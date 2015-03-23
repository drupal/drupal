<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\PageCacheTagsIntegrationTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\simpletest\WebTestBase;

/**
 * Enables the page cache and tests its cache tags in various scenarios.
 *
 * @group Cache
 * @see \Drupal\system\Tests\Bootstrap\PageCacheTest
 * @see \Drupal\node\Tests\NodePageCacheTest
 * @see \Drupal\menu_ui\Tests\MenuTest::testMenuBlockPageCacheTags()
 */
class PageCacheTagsIntegrationTest extends WebTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  protected $profile = 'standard';

  protected $dumpHeaders = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enablePageCaching();
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
      'languages',
      'route.menu_active_trails:account',
      'route.menu_active_trails:footer',
      'route.menu_active_trails:main',
      'route.menu_active_trails:tools',
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
      'config:block.block.bartik_messages',
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
      'config:block.block.bartik_messages',
      'node_view',
      'node:' . $node_2->id(),
      'user:' . $author_2->id(),
      'config:filter.format.full_html',
      'config:system.menu.account',
      'config:system.menu.tools',
      'config:system.menu.footer',
      'config:system.menu.main',
      'config:system.site',
      'comment_list',
      'node_list',
      'config:views.view.comments_recent',
    ));
  }

}

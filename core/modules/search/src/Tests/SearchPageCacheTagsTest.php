<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchPageCacheTagsTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests the search_page entity cache tags on the search results pages.
 *
 * @group search
 */
class SearchPageCacheTagsTest extends SearchTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dumpHeaders = TRUE;

  /**
   * A user with permission to search content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $searchingUser;

  /**
   * A node that is indexed by the search module.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable the page cache.
    // @todo Remove in https://www.drupal.org/node/606840.
    $config = $this->config('system.performance');
    $config->set('cache.page.use_internal', 1);
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Create user.
    $this->searchingUser = $this->drupalCreateUser(array('search content', 'access user profiles'));

    // Create a node and update the search index.
    $this->node = $this->drupalCreateNode(['title' => 'bike shed shop']);
    $this->container->get('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();
  }

  /**
   * Tests the presence of the expected cache tag in various situations.
   */
  function testSearchText() {
    $this->drupalLogin($this->searchingUser);

    // Initial page for searching nodes.
    $this->drupalGet('search/node');
    $this->assertCacheTag('config:search.page.node_search');
    $this->assertCacheTag('search_index:node_search');

    // Node search results.
    $edit = array();
    $edit['keys'] = 'bike shed';
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText('bike shed shop');
    $this->assertCacheTag('config:search.page.node_search');
    $this->assertCacheTag('search_index');
    $this->assertCacheTag('search_index:node_search');

    // Updating a node should invalidate the search plugin's index cache tag.
    $this->node->title = 'bike shop';
    $this->node->save();
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText('bike shop');
    $this->assertCacheTag('config:search.page.node_search');
    $this->assertCacheTag('search_index');
    $this->assertCacheTag('search_index:node_search');

    // Deleting a node should invalidate the search plugin's index cache tag.
    $this->node->delete();
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $this->assertText('Your search yielded no results.');
    $this->assertCacheTag('config:search.page.node_search');
    $this->assertCacheTag('search_index');
    $this->assertCacheTag('search_index:node_search');

    // Initial page for searching users.
    $this->drupalGet('search/user');
    $this->assertCacheTag('config:search.page.user_search');
    $this->assertNoCacheTag('search_index');
    $this->assertNoCacheTag('search_index:user_search');

    // User search results.
    $edit['keys'] = $this->searchingUser->getUsername();
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertCacheTag('config:search.page.user_search');
    $this->assertNoCacheTag('search_index');
    $this->assertNoCacheTag('search_index:user_search');
  }

}

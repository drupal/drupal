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

  protected $dumpHeaders = TRUE;

  protected $searching_user;

  function setUp() {
    parent::setUp();

    // Create user.
    $this->searching_user = $this->drupalCreateUser(array('search content', 'access user profiles'));
  }

  /**
   * Tests the presence of the expected cache tag in various situations.
   */
  function testSearchText() {
    $this->drupalLogin($this->searching_user);

    // Initial page for searching nodes.
    $this->drupalGet('search/node');
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array('search_page:node_search', $cache_tags));

    // Node search results.
    $edit = array();
    $edit['keys'] = 'bike shed';
    $this->drupalPostForm('search/node', $edit, t('Search'));
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array('search_page:node_search', $cache_tags));

    // Initial page for searching users.
    $this->drupalGet('search/user');
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array('search_page:user_search', $cache_tags));

    // User search results.
    $edit['keys'] = $this->searching_user->getUsername();
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array('search_page:user_search', $cache_tags));
  }

}

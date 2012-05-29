<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchPageOverrideTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests that hook_search_page runs.
 */
class SearchPageOverrideTest extends SearchTestBase {
  public $search_user;

  public static function getInfo() {
    return array(
      'name' => 'Search page override',
      'description' => 'Verify that hook_search_page can override search page display.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp(array('search_extra_type'));

    // Login as a user that can create and search content.
    $this->search_user = $this->drupalCreateUser(array('search content', 'administer search'));
    $this->drupalLogin($this->search_user);

    // Enable the extra type module for searching.
    variable_set('search_active_modules', array('node' => 'node', 'user' => 'user', 'search_extra_type' => 'search_extra_type'));
    menu_router_rebuild();
  }

  function testSearchPageHook() {
    $keys = 'bike shed ' . $this->randomName();
    $this->drupalGet("search/dummy_path/{$keys}");
    $this->assertText('Dummy search snippet', 'Dummy search snippet is shown');
    $this->assertText('Test page text is here', 'Page override is working');
  }
}

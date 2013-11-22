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

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('search_extra_type');

  public $search_user;

  public static function getInfo() {
    return array(
      'name' => 'Search page override',
      'description' => 'Verify that hook_search_page can override search page display.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Login as a user that can create and search content.
    $this->search_user = $this->drupalCreateUser(array('search content', 'administer search'));
    $this->drupalLogin($this->search_user);

    // Enable the extra type module for searching.
    \Drupal::config('search.settings')->set('active_plugins', array('node_search', 'user_search', 'search_extra_type_search'))->save();
    \Drupal::state()->set('menu_rebuild_needed', TRUE);
  }

  function testSearchPageHook() {
    $keys = 'bike shed ' . $this->randomName();
    $this->drupalGet("search/dummy_path/{$keys}");
    $this->assertText('Dummy search snippet', 'Dummy search snippet is shown');
    $this->assertText('Test page text is here', 'Page override is working');
  }
}

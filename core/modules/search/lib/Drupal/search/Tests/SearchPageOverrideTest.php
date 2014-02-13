<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchPageOverrideTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests if the result page can be overridden.
 *
 * Verifies that a plugin can override the buildResults() method to
 * control what the search results page looks like.
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
      'description' => 'Verifies that the result page can be overridden.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Login as a user that can create and search content.
    $this->search_user = $this->drupalCreateUser(array('search content', 'administer search'));
    $this->drupalLogin($this->search_user);
  }

  function testSearchPageHook() {
    $keys = 'bike shed ' . $this->randomName();
    $this->drupalGet("search/dummy_path/{$keys}");
    $this->assertText('Dummy search snippet', 'Dummy search snippet is shown');
    $this->assertText('Test page text is here', 'Page override is working');
  }
}

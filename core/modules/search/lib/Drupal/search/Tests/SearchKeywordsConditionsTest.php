<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchKeywordsConditionsTest.
 */

namespace Drupal\search\Tests;

/**
 * Tests the searching without keywords.
 *
 * Verifies that a plugin can override the isSearchExecutable() method to allow
 * searching without keywords set and that GET query parameters are made
 * available to plugins during search execution.
 */
class SearchKeywordsConditionsTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'search_extra_type');

  public static function getInfo() {
    return array(
      'name' => 'Keywords and conditions',
      'description' => 'Verify the search without keywords set and extra conditions.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Create searching user.
    $this->searching_user = $this->drupalCreateUser(array('search content', 'access content', 'access comments', 'skip comment approval'));
    // Login with sufficient privileges.
    $this->drupalLogin($this->searching_user);
  }

  /**
   * Verify the kewords are captured and conditions respected.
   */
  function testSearchKeyswordsConditions() {
    // No keys, not conditions - no results.
    $this->drupalGet('search/dummy_path');
    $this->assertNoText('Dummy search snippet to display');
    // With keys - get results.
    $keys = 'bike shed ' . $this->randomName();
    $this->drupalGet("search/dummy_path/{$keys}");
    $this->assertText("Dummy search snippet to display. Keywords: {$keys}");
    $keys = 'blue drop ' . $this->randomName();
    $this->drupalGet("search/dummy_path", array('query' => array('keys' => $keys)));
    $this->assertText("Dummy search snippet to display. Keywords: {$keys}");
    // Add some conditions and keys.
    $keys = 'moving drop ' . $this->randomName();
    $this->drupalGet("search/dummy_path/bike", array('query' => array('search_conditions' => $keys)));
    $this->assertText("Dummy search snippet to display.");
    $this->assertRaw(print_r(array('search_conditions' => $keys), TRUE));
    // Add some conditions and no keys.
    $keys = 'drop kick ' . $this->randomName();
    $this->drupalGet("search/dummy_path", array('query' => array('search_conditions' => $keys)));
    $this->assertText("Dummy search snippet to display.");
    $this->assertRaw(print_r(array('search_conditions' => $keys), TRUE));
  }
}

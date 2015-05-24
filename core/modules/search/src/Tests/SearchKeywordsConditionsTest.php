<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchKeywordsConditionsTest.
 */

namespace Drupal\search\Tests;

/**
 * Verify the search without keywords set and extra conditions.
 *
 * Verifies that a plugin can override the isSearchExecutable() method to allow
 * searching without keywords set and that GET query parameters are made
 * available to plugins during search execution.
 *
 * @group search
 */
class SearchKeywordsConditionsTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'search_extra_type', 'test_page_test');

  /**
   * A user with permission to search and post comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $searchingUser;

  protected function setUp() {
    parent::setUp();

    // Create searching user.
    $this->searchingUser = $this->drupalCreateUser(array('search content', 'access content', 'access comments', 'skip comment approval'));
    // Login with sufficient privileges.
    $this->drupalLogin($this->searchingUser);
  }

  /**
   * Verify the keywords are captured and conditions respected.
   */
  function testSearchKeywordsConditions() {
    // No keys, not conditions - no results.
    $this->drupalGet('search/dummy_path');
    $this->assertNoText('Dummy search snippet to display');
    // With keys - get results.
    $keys = 'bike shed ' . $this->randomMachineName();
    $this->drupalGet("search/dummy_path", array('query' => array('keys' => $keys)));
    $this->assertText("Dummy search snippet to display. Keywords: {$keys}");
    $keys = 'blue drop ' . $this->randomMachineName();
    $this->drupalGet("search/dummy_path", array('query' => array('keys' => $keys)));
    $this->assertText("Dummy search snippet to display. Keywords: {$keys}");
    // Add some conditions and keys.
    $keys = 'moving drop ' . $this->randomMachineName();
    $this->drupalGet("search/dummy_path", array('query' => array('keys' => 'bike', 'search_conditions' => $keys)));
    $this->assertText("Dummy search snippet to display.");
    $this->assertRaw(print_r(array('keys' => 'bike', 'search_conditions' => $keys), TRUE));
  }
}

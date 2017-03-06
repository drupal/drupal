<?php

namespace Drupal\Tests\search\Functional;

/**
 * Tests if the result page can be overridden.
 *
 * Verifies that a plugin can override the buildResults() method to
 * control what the search results page looks like.
 *
 * @group search
 */
class SearchPageOverrideTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['search_extra_type'];

  /**
   * A user with permission to administer search.
   *
   * @var \Drupal\user\UserInterface
   */
  public $searchUser;

  protected function setUp() {
    parent::setUp();

    // Log in as a user that can create and search content.
    $this->searchUser = $this->drupalCreateUser(['search content', 'administer search']);
    $this->drupalLogin($this->searchUser);
  }

  public function testSearchPageHook() {
    $keys = 'bike shed ' . $this->randomMachineName();
    $this->drupalGet("search/dummy_path", ['query' => ['keys' => $keys]]);
    $this->assertText('Dummy search snippet', 'Dummy search snippet is shown');
    $this->assertText('Test page text is here', 'Page override is working');
  }

}

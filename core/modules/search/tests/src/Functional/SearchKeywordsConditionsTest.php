<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify the search without keywords set and extra conditions.
 *
 * Verifies that a plugin can override the isSearchExecutable() method to allow
 * searching without keywords set and that GET query parameters are made
 * available to plugins during search execution.
 *
 * @group search
 */
class SearchKeywordsConditionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'search',
    'search_extra_type',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to search and post comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $searchingUser;

  protected function setUp(): void {
    parent::setUp();

    // Create searching user.
    $this->searchingUser = $this->drupalCreateUser([
      'search content',
      'access content',
      'access comments',
      'skip comment approval',
    ]);
    // Log in with sufficient privileges.
    $this->drupalLogin($this->searchingUser);
  }

  /**
   * Verify the keywords are captured and conditions respected.
   */
  public function testSearchKeywordsConditions() {
    // No keys, not conditions - no results.
    $this->drupalGet('search/dummy_path');
    $this->assertSession()->pageTextNotContains('Dummy search snippet to display');
    // With keys - get results.
    $keys = 'bike shed ' . $this->randomMachineName();
    $this->drupalGet("search/dummy_path", ['query' => ['keys' => $keys]]);
    $this->assertSession()->pageTextContains("Dummy search snippet to display. Keywords: {$keys}");
    $keys = 'blue drop ' . $this->randomMachineName();
    $this->drupalGet("search/dummy_path", ['query' => ['keys' => $keys]]);
    $this->assertSession()->pageTextContains("Dummy search snippet to display. Keywords: {$keys}");
    // Add some conditions and keys.
    $keys = 'moving drop ' . $this->randomMachineName();
    $this->drupalGet("search/dummy_path", ['query' => ['keys' => 'bike', 'search_conditions' => $keys]]);
    $this->assertSession()->pageTextContains("Dummy search snippet to display.");
    $this->assertSession()->responseContains(Html::escape(print_r(['keys' => 'bike', 'search_conditions' => $keys], TRUE)));
  }

}

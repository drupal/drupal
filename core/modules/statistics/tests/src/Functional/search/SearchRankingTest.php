<?php

declare(strict_types=1);

namespace Drupal\Tests\statistics\Functional\search;

use Drupal\Core\Database\Database;
use Drupal\search\Entity\SearchPage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

// cspell:ignore daycount totalcount

/**
 * Indexes content and tests ranking factors.
 *
 * @group statistics
 * @group legacy
 */
class SearchRankingTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * The node search page.
   *
   * @var \Drupal\search\SearchPageInterface
   */
  protected $nodeSearch;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'search', 'statistics'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a plugin instance.
    $this->nodeSearch = SearchPage::load('node_search');

    // Log in with sufficient privileges.
    $this->drupalLogin($this->drupalCreateUser([
      'create page content',
      'administer search',
    ]));
  }

  /**
   * Tests statistics ranking on search pages.
   */
  public function testRankings(): void {
    // Create nodes for testing.
    $nodes = [];
    $settings = [
      'type' => 'page',
      'title' => 'Drupal rocks',
      'body' => [['value' => "Drupal's search rocks"]],
      // Node is one day old.
      'created' => \Drupal::time()->getRequestTime() - 24 * 3600,
      'sticky' => 0,
      'promote' => 0,
    ];
    foreach ([0, 1] as $num) {
      $nodes['views'][$num] = $this->drupalCreateNode($settings);
    }

    // Enable counting of statistics.
    $this->config('statistics.settings')->set('count_content_views', 1)->save();

    // Simulating content views is kind of difficult in the test. So instead go
    // ahead and manually update the counter for this node.
    $nid = $nodes['views'][1]->id();
    Database::getConnection()->insert('node_counter')
      ->fields([
        'totalcount' => 5,
        'daycount' => 5,
        'timestamp' => \Drupal::time()->getRequestTime(),
        'nid' => $nid,
      ])
      ->execute();

    // Run cron to update the search index and statistics totals.
    $this->cronRun();

    // Test that the settings form displays the content ranking section.
    $this->drupalGet('admin/config/search/pages/manage/node_search');
    $this->assertSession()->pageTextContains('Content ranking');

    // Check that views ranking is visible and set to 0.
    $this->assertSession()->optionExists('edit-rankings-views-value', '0');

    // Test each of the possible rankings.
    $edit = [];

    // Enable views ranking.
    $edit['rankings[views][value]'] = 10;
    $this->drupalGet('admin/config/search/pages/manage/node_search');
    $this->submitForm($edit, 'Save search page');
    $this->drupalGet('admin/config/search/pages/manage/node_search');
    $this->assertSession()->optionExists('edit-rankings-views-value', '10');

    // Reload the plugin to get the up-to-date values.
    $this->nodeSearch = SearchPage::load('node_search');
    // Do the search and assert the results.
    $this->nodeSearch->getPlugin()->setSearch('rocks', [], []);
    $set = $this->nodeSearch->getPlugin()->execute();
    $this->assertEquals($nodes['views'][1]->id(), $set[0]['node']->id());
  }

}

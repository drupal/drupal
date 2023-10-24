<?php

namespace Drupal\Tests\search\Functional;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\search\Entity\SearchPage;
use Drupal\search\SearchIndexInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Indexes content and tests ranking factors.
 *
 * @group search
 */
class SearchRankingTest extends BrowserTestBase {

  use CommentTestTrait;
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
  protected static $modules = ['node', 'search', 'comment'];

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
      'post comments',
      'skip comment approval',
      'create page content',
      'administer search',
    ]));
  }

  public function testRankings() {
    // Add a comment field.
    $this->addDefaultCommentField('node', 'page');

    // Build a list of the rankings to test.
    $node_ranks = ['sticky', 'promote', 'relevance', 'recent', 'comments'];

    // Create nodes for testing.
    $nodes = [];
    foreach ($node_ranks as $node_rank) {
      $settings = [
        'type' => 'page',
        'comment' => [
          ['status' => CommentItemInterface::HIDDEN],
        ],
        'title' => 'Drupal rocks',
        'body' => [['value' => "Drupal's search rocks"]],
        // Node is one day old.
        'created' => REQUEST_TIME - 24 * 3600,
        'sticky' => 0,
        'promote' => 0,
      ];
      foreach ([0, 1] as $num) {
        if ($num == 1) {
          switch ($node_rank) {
            case 'sticky':
            case 'promote':
              $settings[$node_rank] = 1;
              break;

            case 'relevance':
              $settings['body'][0]['value'] .= " really rocks";
              break;

            case 'recent':
              // Node is 1 hour hold.
              $settings['created'] = REQUEST_TIME - 3600;
              break;

            case 'comments':
              $settings['comment'][0]['status'] = CommentItemInterface::OPEN;
              break;
          }
        }
        $nodes[$node_rank][$num] = $this->drupalCreateNode($settings);
      }
    }

    // Add a comment to one of the nodes.
    $edit = [];
    $edit['subject[0][value]'] = 'my comment title';
    $edit['comment_body[0][value]'] = 'some random comment';
    $this->drupalGet('comment/reply/node/' . $nodes['comments'][1]->id() . '/comment');
    $this->submitForm($edit, 'Preview');
    $this->submitForm($edit, 'Save');

    // Run cron to update the search index totals.
    $this->cronRun();

    // Test that the settings form displays the content ranking section.
    $this->drupalGet('admin/config/search/pages/manage/node_search');
    $this->assertSession()->pageTextContains('Content ranking');

    // Check that all rankings are visible and set to 0.
    foreach ($node_ranks as $node_rank) {
      $this->assertSession()->optionExists('edit-rankings-' . $node_rank . '-value', '0');
    }

    // Test each of the possible rankings.
    $edit = [];
    foreach ($node_ranks as $node_rank) {
      // Enable the ranking we are testing.
      $edit['rankings[' . $node_rank . '][value]'] = 10;
      $this->drupalGet('admin/config/search/pages/manage/node_search');
      $this->submitForm($edit, 'Save search page');
      $this->drupalGet('admin/config/search/pages/manage/node_search');
      $this->assertSession()->optionExists('edit-rankings-' . $node_rank . '-value', '10');

      // Reload the plugin to get the up-to-date values.
      $this->nodeSearch = SearchPage::load('node_search');
      // Do the search and assert the results.
      $this->nodeSearch->getPlugin()->setSearch('rocks', [], []);
      $set = $this->nodeSearch->getPlugin()->execute();
      $this->assertEquals($nodes[$node_rank][1]->id(), $set[0]['node']->id(), 'Search ranking "' . $node_rank . '" order.');

      // Clear this ranking for the next test.
      $edit['rankings[' . $node_rank . '][value]'] = 0;
    }

    // Save the final node_rank change then check that all rankings are visible
    // and have been set back to 0.
    $this->drupalGet('admin/config/search/pages/manage/node_search');
    $this->submitForm($edit, 'Save search page');
    $this->drupalGet('admin/config/search/pages/manage/node_search');
    foreach ($node_ranks as $node_rank) {
      $this->assertSession()->optionExists('edit-rankings-' . $node_rank . '-value', '0');
    }

    // Try with sticky, then promoted. This is a test for issue
    // https://www.drupal.org/node/771596.
    $node_ranks = [
      'sticky' => 10,
      'promote' => 1,
      'relevance' => 0,
      'recent' => 0,
      'comments' => 0,
    ];
    $configuration = $this->nodeSearch->getPlugin()->getConfiguration();
    foreach ($node_ranks as $var => $value) {
      $configuration['rankings'][$var] = $value;
    }
    $this->nodeSearch->getPlugin()->setConfiguration($configuration);
    $this->nodeSearch->save();

    // Do the search and assert the results. The sticky node should show up
    // first, then the promoted node, then all the rest.
    $this->nodeSearch->getPlugin()->setSearch('rocks', [], []);
    $set = $this->nodeSearch->getPlugin()->execute();
    $this->assertEquals($nodes['sticky'][1]->id(), $set[0]['node']->id(), 'Search ranking for sticky first worked.');
    $this->assertEquals($nodes['promote'][1]->id(), $set[1]['node']->id(), 'Search ranking for promoted second worked.');

    // Try with recent, then comments. This is a test for issues
    // https://www.drupal.org/node/771596 and
    // https://www.drupal.org/node/303574.
    $node_ranks = [
      'sticky' => 0,
      'promote' => 0,
      'relevance' => 0,
      'recent' => 10,
      'comments' => 1,
    ];
    $configuration = $this->nodeSearch->getPlugin()->getConfiguration();
    foreach ($node_ranks as $var => $value) {
      $configuration['rankings'][$var] = $value;
    }
    $this->nodeSearch->getPlugin()->setConfiguration($configuration);
    $this->nodeSearch->save();

    // Do the search and assert the results. The recent node should show up
    // first, then the commented node, then all the rest.
    $this->nodeSearch->getPlugin()->setSearch('rocks', [], []);
    $set = $this->nodeSearch->getPlugin()->execute();
    $this->assertEquals($nodes['recent'][1]->id(), $set[0]['node']->id(), 'Search ranking for recent first worked.');
    $this->assertEquals($nodes['comments'][1]->id(), $set[1]['node']->id(), 'Search ranking for comments second worked.');

  }

  /**
   * Tests rankings of HTML tags.
   */
  public function testHTMLRankings() {
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $full_html_format->save();

    // Test HTML tags with different weights.
    $sorted_tags = ['h1', 'h2', 'h3', 'h4', 'a', 'h5', 'h6', 'NoTag'];
    $shuffled_tags = $sorted_tags;

    // Shuffle tags to ensure HTML tags are ranked properly.
    shuffle($shuffled_tags);
    $settings = [
      'type' => 'page',
      'title' => 'Simple node',
    ];
    $nodes = [];
    foreach ($shuffled_tags as $tag) {
      switch ($tag) {
        case 'a':
          $settings['body'] = [['value' => Link::fromTextAndUrl('Drupal Rocks', Url::fromRoute('<front>'))->toString(), 'format' => 'full_html']];
          break;

        case 'NoTag':
          $settings['body'] = [['value' => 'Drupal Rocks']];
          break;

        default:
          $settings['body'] = [['value' => "<$tag>Drupal Rocks</$tag>", 'format' => 'full_html']];
          break;
      }
      $nodes[$tag] = $this->drupalCreateNode($settings);
    }

    // Update the search index.
    $this->nodeSearch->getPlugin()->updateIndex();
    $search_index = \Drupal::service('search.index');
    assert($search_index instanceof SearchIndexInterface);

    $this->nodeSearch->getPlugin()->setSearch('rocks', [], []);
    // Do the search and assert the results.
    $set = $this->nodeSearch->getPlugin()->execute();

    // Test the ranking of each tag.
    foreach ($sorted_tags as $tag_rank => $tag) {
      // Assert the results.
      if ($tag == 'NoTag') {
        $this->assertEquals($nodes[$tag]->id(), $set[$tag_rank]['node']->id(), 'Search tag ranking for plain text order.');
      }
      else {
        $this->assertEquals($nodes[$tag]->id(), $set[$tag_rank]['node']->id(), 'Search tag ranking for "&lt;' . $sorted_tags[$tag_rank] . '&gt;" order.');
      }
    }

    // Test tags with the same weight against the sorted tags.
    $unsorted_tags = ['u', 'b', 'i', 'strong', 'em'];
    foreach ($unsorted_tags as $tag) {
      $settings['body'] = [['value' => "<$tag>Drupal Rocks</$tag>", 'format' => 'full_html']];
      $node = $this->drupalCreateNode($settings);

      // Update the search index.
      $this->nodeSearch->getPlugin()->updateIndex();

      $this->nodeSearch->getPlugin()->setSearch('rocks', [], []);
      // Do the search and assert the results.
      $set = $this->nodeSearch->getPlugin()->execute();

      // Ranking should always be second to last.
      $set = array_slice($set, -2, 1);

      // Assert the results.
      $this->assertEquals($node->id(), $set[0]['node']->id(), 'Search tag ranking for "&lt;' . $tag . '&gt;" order.');

      // Delete node so it doesn't show up in subsequent search results.
      $node->delete();
    }
  }

}

<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchRankingTest.
 */

namespace Drupal\search\Tests;

use Drupal\Core\Language\Language;

class SearchRankingTest extends SearchTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('statistics', 'comment');

  public static function getInfo() {
    return array(
      'name' => 'Search engine ranking',
      'description' => 'Indexes content and tests ranking factors.',
      'group' => 'Search',
    );
  }

  function testRankings() {
    // Login with sufficient privileges.
    $this->drupalLogin($this->drupalCreateUser(array('post comments', 'skip comment approval', 'create page content')));

    // Build a list of the rankings to test.
    $node_ranks = array('sticky', 'promote', 'relevance', 'recent', 'comments', 'views');

    // Create nodes for testing.
    foreach ($node_ranks as $node_rank) {
      $settings = array(
        'type' => 'page',
        'title' => 'Drupal rocks',
        'body' => array(array('value' => "Drupal's search rocks")),
      );
      foreach (array(0, 1) as $num) {
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
              $settings['created'] = REQUEST_TIME + 3600;
              break;
            case 'comments':
              $settings['comment'] = 2;
              break;
          }
        }
        $nodes[$node_rank][$num] = $this->drupalCreateNode($settings);
      }
    }

    // Update the search index.
    module_invoke_all('update_index');
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Add a comment to one of the nodes.
    $edit = array();
    $edit['subject'] = 'my comment title';
    $edit['comment_body[' . Language::LANGCODE_NOT_SPECIFIED . '][0][value]'] = 'some random comment';
    $this->drupalGet('comment/reply/' . $nodes['comments'][1]->id());
    $this->drupalPost(NULL, $edit, t('Preview'));
    $this->drupalPost(NULL, $edit, t('Save'));

    // Enable counting of statistics.
    \Drupal::config('statistics.settings')->set('count_content_views', 1)->save();

    // Then View one of the nodes a bunch of times.
    // Manually calling statistics.php, simulating ajax behavior.
    $client = \Drupal::httpClient();
    $client->setConfig(array('curl.options' => array(CURLOPT_TIMEOUT => 10)));
    $nid = $nodes['views'][1]->id();
    global $base_url;
    $stats_path = $base_url . '/' . drupal_get_path('module', 'statistics'). '/statistics.php';
    for ($i = 0; $i < 5; $i ++) {
      $client->post($stats_path, array(), array('nid' => $nid))->send();
    }

    // Test each of the possible rankings.
    foreach ($node_ranks as $node_rank) {
      // Disable all relevancy rankings except the one we are testing.
      foreach ($node_ranks as $var) {
        variable_set('node_rank_' . $var, $var == $node_rank ? 10 : 0);
      }

      // Do the search and assert the results.
      $set = node_search_execute('rocks');
      $this->assertEqual($set[0]['node']->id(), $nodes[$node_rank][1]->id(), 'Search ranking "' . $node_rank . '" order.');
    }
  }

  /**
   * Test rankings of HTML tags.
   */
  function testHTMLRankings() {
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    ));
    $full_html_format->save();

    // Login with sufficient privileges.
    $this->drupalLogin($this->drupalCreateUser(array('create page content')));

    // Test HTML tags with different weights.
    $sorted_tags = array('h1', 'h2', 'h3', 'h4', 'a', 'h5', 'h6', 'notag');
    $shuffled_tags = $sorted_tags;

    // Shuffle tags to ensure HTML tags are ranked properly.
    shuffle($shuffled_tags);
    $settings = array(
      'type' => 'page',
      'title' => 'Simple node',
    );
    foreach ($shuffled_tags as $tag) {
      switch ($tag) {
        case 'a':
          $settings['body'] = array(array('value' => l('Drupal Rocks', 'node'), 'format' => 'full_html'));
          break;
        case 'notag':
          $settings['body'] = array(array('value' => 'Drupal Rocks'));
          break;
        default:
          $settings['body'] = array(array('value' => "<$tag>Drupal Rocks</$tag>", 'format' => 'full_html'));
          break;
      }
      $nodes[$tag] = $this->drupalCreateNode($settings);
    }

    // Update the search index.
    module_invoke_all('update_index');
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Disable all other rankings.
    $node_ranks = array('sticky', 'promote', 'recent', 'comments', 'views');
    foreach ($node_ranks as $node_rank) {
      variable_set('node_rank_' . $node_rank, 0);
    }
    $set = node_search_execute('rocks');

    // Test the ranking of each tag.
    foreach ($sorted_tags as $tag_rank => $tag) {
      // Assert the results.
      if ($tag == 'notag') {
        $this->assertEqual($set[$tag_rank]['node']->id(), $nodes[$tag]->id(), 'Search tag ranking for plain text order.');
      } else {
        $this->assertEqual($set[$tag_rank]['node']->id(), $nodes[$tag]->id(), 'Search tag ranking for "&lt;' . $sorted_tags[$tag_rank] . '&gt;" order.');
      }
    }

    // Test tags with the same weight against the sorted tags.
    $unsorted_tags = array('u', 'b', 'i', 'strong', 'em');
    foreach ($unsorted_tags as $tag) {
      $settings['body'] = array(array('value' => "<$tag>Drupal Rocks</$tag>", 'format' => 'full_html'));
      $node = $this->drupalCreateNode($settings);

      // Update the search index.
      module_invoke_all('update_index');
      search_update_totals();

      // Refresh variables after the treatment.
      $this->refreshVariables();

      $set = node_search_execute('rocks');

      // Ranking should always be second to last.
      $set = array_slice($set, -2, 1);

      // Assert the results.
      $this->assertEqual($set[0]['node']->id(), $node->id(), 'Search tag ranking for "&lt;' . $tag . '&gt;" order.');

      // Delete node so it doesn't show up in subsequent search results.
      $node->delete();
    }
  }

  /**
   * Verifies that if we combine two rankings, search still works.
   *
   * See issue http://drupal.org/node/771596
   */
  function testDoubleRankings() {
    // Login with sufficient privileges.
    $this->drupalLogin($this->drupalCreateUser(array('skip comment approval', 'create page content')));

    // See testRankings() above - build a node that will rank high for sticky.
    $settings = array(
      'type' => 'page',
      'title' => 'Drupal rocks',
      'body' => array(array('value' => "Drupal's search rocks")),
      'sticky' => 1,
    );

    $node = $this->drupalCreateNode($settings);

    // Update the search index.
    module_invoke_all('update_index');
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Set up for ranking sticky and lots of comments; make sure others are
    // disabled.
    $node_ranks = array('sticky', 'promote', 'relevance', 'recent', 'comments', 'views');
    foreach ($node_ranks as $var) {
      $value = ($var == 'sticky' || $var == 'comments') ? 10 : 0;
      variable_set('node_rank_' . $var, $value);
    }

    // Do the search and assert the results.
    $set = node_search_execute('rocks');
    $this->assertEqual($set[0]['node']->id(), $node->id(), 'Search double ranking order.');
  }
}

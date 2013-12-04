<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchRankingTest.
 */

namespace Drupal\search\Tests;

class SearchRankingTest extends SearchTestBase {

  /**
   * A node search plugin instance.
   *
   * @var \Drupal\search\Plugin\SearchInterface
   */
  protected $nodeSearchPlugin;

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

  public function setUp() {
    parent::setUp();

    // Create a plugin instance.
    $this->nodeSearchPlugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
  }

  public function testRankings() {
    // Login with sufficient privileges.
    $this->drupalLogin($this->drupalCreateUser(array('post comments', 'skip comment approval', 'create page content', 'administer search')));
    // Add a comment field.
    $this->container->get('comment.manager')->addDefaultField('node', 'page');

    // Build a list of the rankings to test.
    $node_ranks = array('sticky', 'promote', 'relevance', 'recent', 'comments', 'views');

    // Create nodes for testing.
    foreach ($node_ranks as $node_rank) {
      $settings = array(
        'type' => 'page',
        'comment' => array(array(
          'status' => COMMENT_HIDDEN,
        )),
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
              $settings['comment'][0]['status'] = COMMENT_OPEN;
              break;
          }
        }
        $nodes[$node_rank][$num] = $this->drupalCreateNode($settings);
      }
    }

    // Update the search index.
    $this->nodeSearchPlugin->updateIndex();
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Add a comment to one of the nodes.
    $edit = array();
    $edit['subject'] = 'my comment title';
    $edit['comment_body[0][value]'] = 'some random comment';
    $this->drupalGet('comment/reply/node/' . $nodes['comments'][1]->id() . '/comment');
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->drupalPostForm(NULL, $edit, t('Save'));

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

    // @todo - comments and views are removed from the array since they are
    // broken in core. Those modules expected hook_update_index() to be called
    // even though it was only called on modules that implemented a search type.
    array_pop($node_ranks);
    array_pop($node_ranks);

    // Test that the settings form displays the context ranking section.
    $this->drupalGet('admin/config/search/settings');
    $this->assertText(t('Content ranking'));

    // Check that all rankings are visible and set to 0.
    foreach ($node_ranks as $node_rank) {
      $this->assertTrue($this->xpath('//select[@id="edit-node-rank-' . $node_rank . '"]//option[@value="0"]'), 'Select list to prioritize ' . $node_rank . ' for node ranks is visible and set to 0.');
    }

    // Test each of the possible rankings.
    $edit = array();
    foreach ($node_ranks as $node_rank) {
      // Enable the ranking we are testing.
      $edit['node_rank_' . $node_rank] = 10;
      $this->drupalPostForm('admin/config/search/settings', $edit, t('Save configuration'));
      $this->assertTrue($this->xpath('//select[@id="edit-node-rank-' . $node_rank . '"]//option[@value="10"]'), 'Select list to prioritize ' . $node_rank . ' for node ranks is visible and set to 10.');

      // Do the search and assert the results.
      $this->nodeSearchPlugin->setSearch('rocks', array(), array());
      $set = $this->nodeSearchPlugin->execute();
      $this->assertEqual($set[0]['node']->id(), $nodes[$node_rank][1]->id(), 'Search ranking "' . $node_rank . '" order.');
      // Clear this ranking for the next test.
      $edit['node_rank_' . $node_rank] = 0;
    }

    // Save the final node_rank change then check that all rankings are visible
    // and have been set back to 0.
    $this->drupalPostForm('admin/config/search/settings', $edit, t('Save configuration'));
    foreach ($node_ranks as $node_rank) {
      $this->assertTrue($this->xpath('//select[@id="edit-node-rank-' . $node_rank . '"]//option[@value="0"]'), 'Select list to prioritize ' . $node_rank . ' for node ranks is visible and set to 0.');
    }
  }

  /**
   * Test rankings of HTML tags.
   */
  public function testHTMLRankings() {
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
    $this->nodeSearchPlugin->updateIndex();
    search_update_totals();

    // Refresh variables after the treatment.
    $this->refreshVariables();

    // Disable all other rankings.
    $node_ranks = array('sticky', 'promote', 'recent', 'comments', 'views');
    foreach ($node_ranks as $node_rank) {
      variable_set('node_rank_' . $node_rank, 0);
    }
    $this->nodeSearchPlugin->setSearch('rocks', array(), array());
    // Do the search and assert the results.
    $set = $this->nodeSearchPlugin->execute();

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
      $this->nodeSearchPlugin->updateIndex();
      search_update_totals();

      // Refresh variables after the treatment.
      $this->refreshVariables();
      $this->nodeSearchPlugin->setSearch('rocks', array(), array());
      // Do the search and assert the results.
      $set = $this->nodeSearchPlugin->execute();

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
    $this->nodeSearchPlugin->updateIndex();
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
    $this->nodeSearchPlugin->setSearch('rocks', array(), array());
    // Do the search and assert the results.
    $set = $this->nodeSearchPlugin->execute();
    $this->assertEqual($set[0]['node']->id(), $node->id(), 'Search double ranking order.');
  }
}

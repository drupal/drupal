<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchMultilingualEntityTest.
 */

namespace Drupal\search\Tests;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests entities with multilingual fields.
 *
 * @group search
 */
class SearchMultilingualEntityTest extends SearchTestBase {

  /**
   * List of searchable nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $searchableNodes = array();

  /**
   * Node search plugin.
   *
   * @var \Drupal\node\Plugin\Search\NodeSearch
   */
  protected $plugin;

  public static $modules = array('language', 'locale', 'comment');

  protected function setUp() {
    parent::setUp();

    // Create a user who can administer search, do searches, see the status
    // report, and administer cron. Log in.
    $user = $this->drupalCreateUser(array('administer search', 'search content', 'use advanced search', 'access content', 'access site reports', 'administer site configuration'));
    $this->drupalLogin($user);

    // Make sure that auto-cron is disabled.
    $this->config('system.cron')->set('threshold.autorun', 0)->save();

    // Set up the search plugin.
    $this->plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');

    // Check indexing counts before adding any nodes.
    $this->assertIndexCounts(0, 0, 'before adding nodes');
    $this->assertDatabaseCounts(0, 0, 'before adding nodes');

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('sv')->save();

    // Make the body field translatable. The title is already translatable by
    // definition. The parent class has already created the article and page
    // content types.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->translatable = TRUE;
    $field_storage->save();

    // Create a few page nodes with multilingual body values.
    $default_format = filter_default_format();
    $nodes = array(
      array(
        'title' => 'First node en',
        'type' => 'page',
        'body' => array(array('value' => $this->randomMachineName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
      array(
        'title' => 'Second node this is the English title',
        'type' => 'page',
        'body' => array(array('value' => $this->randomMachineName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
      array(
        'title' => 'Third node en',
        'type' => 'page',
        'body' => array(array('value' => $this->randomMachineName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
      // After the third node, we don't care what the settings are. But we
      // need to have at least 5 to make sure the throttling is working
      // correctly. So, let's make 8 total.
      array(
      ),
      array(
      ),
      array(
      ),
      array(
      ),
      array(
      ),
    );
    $this->searchableNodes = array();
    foreach ($nodes as $setting) {
      $this->searchableNodes[] = $this->drupalCreateNode($setting);
    }

    // Add a single translation to the second node.
    $translation = $this->searchableNodes[1]->addTranslation('hu', array('title' => 'Second node hu'));
    $translation->body->value = $this->randomMachineName(32);
    $this->searchableNodes[1]->save();

    // Add two translations to the third node.
    $translation = $this->searchableNodes[2]->addTranslation('hu', array('title' => 'Third node this is the Hungarian title'));
    $translation->body->value = $this->randomMachineName(32);
    $translation = $this->searchableNodes[2]->addTranslation('sv', array('title' => 'Third node sv'));
    $translation->body->value = $this->randomMachineName(32);
    $this->searchableNodes[2]->save();

    // Verify that we have 8 nodes left to do.
    $this->assertIndexCounts(8, 8, 'before updating the search index');
    $this->assertDatabaseCounts(0, 0, 'before updating the search index');
  }

  /**
   * Tests the indexing throttle and search results with multilingual nodes.
   */
  function testMultilingualSearch() {
    // Index only 2 nodes per cron run. We cannot do this setting in the UI,
    // because it doesn't go this low.
    $this->config('search.settings')->set('index.cron_limit', 2)->save();
    // Get a new search plugin, to make sure it has this setting.
    $this->plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');

    // Update the index. This does the initial processing.
    $this->plugin->updateIndex();
    // Run the shutdown function. Testing is a unique case where indexing
    // and searching has to happen in the same request, so running the shutdown
    // function manually is needed to finish the indexing process.
    search_update_totals();
    $this->assertIndexCounts(6, 8, 'after updating partially');
    $this->assertDatabaseCounts(2, 0, 'after updating partially');

    // Now index the rest of the nodes.
    // Make sure index throttle is high enough, via the UI.
    $this->drupalPostForm('admin/config/search/pages', array('cron_limit' => 20), t('Save configuration'));
    $this->assertEqual(20, $this->config('search.settings')->get('index.cron_limit', 100), 'Config setting was saved correctly');
    // Get a new search plugin, to make sure it has this setting.
    $this->plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');

    $this->plugin->updateIndex();
    search_update_totals();
    $this->assertIndexCounts(0, 8, 'after updating fully');
    $this->assertDatabaseCounts(8, 0, 'after updating fully');

    // Click the reindex button on the admin page, verify counts, and reindex.
    $this->drupalPostForm('admin/config/search/pages', array(), t('Re-index site'));
    $this->drupalPostForm(NULL, array(), t('Re-index site'));
    $this->assertIndexCounts(8, 8, 'after reindex');
    $this->assertDatabaseCounts(8, 0, 'after reindex');
    $this->plugin->updateIndex();
    search_update_totals();

    // Test search results.

    // This should find two results for the second and third node.
    $this->plugin->setSearch('English OR Hungarian', array(), array());
    $search_result = $this->plugin->execute();
    $this->assertEqual(count($search_result), 2, 'Found two results.');
    // Nodes are saved directly after each other and have the same created time
    // so testing for the order is not possible.
    $results = array($search_result[0]['title'], $search_result[1]['title']);
    $this->assertTrue(in_array('Third node this is the Hungarian title', $results), 'The search finds the correct Hungarian title.');
    $this->assertTrue(in_array('Second node this is the English title', $results), 'The search finds the correct English title.');

    // Now filter for Hungarian results only.
    $this->plugin->setSearch('English OR Hungarian', array('f' => array('language:hu')), array());
    $search_result = $this->plugin->execute();

    $this->assertEqual(count($search_result), 1, 'The search found only one result');
    $this->assertEqual($search_result[0]['title'], 'Third node this is the Hungarian title', 'The search finds the correct Hungarian title.');

    // Test for search with common key word across multiple languages.
    $this->plugin->setSearch('node', array(), array());
    $search_result = $this->plugin->execute();

    $this->assertEqual(count($search_result), 6, 'The search found total six results');

    // Test with language filters and common key word.
    $this->plugin->setSearch('node', array('f' => array('language:hu')), array());
    $search_result = $this->plugin->execute();

    $this->assertEqual(count($search_result), 2, 'The search found 2 results');

    // Test to check for the language of result items.
    foreach($search_result as $result) {
      $this->assertEqual($result['langcode'], 'hu', 'The search found the correct Hungarian result');
    }

    // Mark one of the nodes for reindexing, using the API function, and
    // verify indexing status.
    search_mark_for_reindex('node_search', $this->searchableNodes[0]->id());
    $this->assertIndexCounts(1, 8, 'after marking one node to reindex via API function');

    // Update the index and verify the totals again.
    $this->plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    $this->plugin->updateIndex();
    search_update_totals();
    $this->assertIndexCounts(0, 8, 'after indexing again');

    // Mark one node for reindexing by saving it, and verify indexing status.
    $this->searchableNodes[1]->save();
    $this->assertIndexCounts(1, 8, 'after marking one node to reindex via save');

    // The request time is always the same throughout test runs. Update the
    // request time to a previous time, to simulate it having been marked
    // previously.
    $current = REQUEST_TIME;
    $old = $current - 10;
    db_update('search_dataset')
      ->fields(array('reindex' => $old))
      ->condition('reindex', $current, '>=')
      ->execute();

    // Save the node again. Verify that the request time on it is not updated.
    $this->searchableNodes[1]->save();
    $result = db_select('search_dataset', 'd')
      ->fields('d', array('reindex'))
      ->condition('type', 'node_search')
      ->condition('sid', $this->searchableNodes[1]->id())
      ->execute()
      ->fetchField();
    $this->assertEqual($result, $old, 'Reindex time was not updated if node was already marked');

    // Add a bogus entry to the search index table using a different search
    // type. This will not appear in the index status, because it is not
    // managed by a plugin.
    search_index('foo', $this->searchableNodes[0]->id(), 'en', 'some text');
    $this->assertIndexCounts(1, 8, 'after adding a different index item');

    // Mark just this "foo" index for reindexing.
    search_mark_for_reindex('foo');
    $this->assertIndexCounts(1, 8, 'after reindexing the other search type');

    // Mark everything for reindexing.
    search_mark_for_reindex();
    $this->assertIndexCounts(8, 8, 'after reindexing everything');

    // Clear one item from the index, but with wrong language.
    $this->assertDatabaseCounts(8, 1, 'before clear');
    search_index_clear('node_search', $this->searchableNodes[0]->id(), 'hu');
    $this->assertDatabaseCounts(8, 1, 'after clear with wrong language');
    // Clear using correct language.
    search_index_clear('node_search', $this->searchableNodes[0]->id(), 'en');
    $this->assertDatabaseCounts(7, 1, 'after clear with right language');
    // Don't specify language.
    search_index_clear('node_search', $this->searchableNodes[1]->id());
    $this->assertDatabaseCounts(6, 1, 'unspecified language clear');
    // Clear everything in 'foo'.
    search_index_clear('foo');
    $this->assertDatabaseCounts(6, 0, 'other index clear');
    // Clear everything.
    search_index_clear();
    $this->assertDatabaseCounts(0, 0, 'complete clear');
  }

  /**
   * Verifies the indexing status counts.
   *
   * @param $remaining
   *   Count of remaining items to verify.
   * @param $total
   *   Count of total items to verify.
   * @param $message
   *   Message to use, something like "after updating the search index".
   */
  protected function assertIndexCounts($remaining, $total, $message) {
    // Check status via plugin method call.
    $status = $this->plugin->indexStatus();
    $this->assertEqual($status['remaining'], $remaining, 'Remaining items ' . $message . ' is ' . $remaining);
    $this->assertEqual($status['total'], $total, 'Total items ' . $message . ' is ' . $total);

    // Check text in progress section of Search settings page. Note that this
    // test avoids using
    // \Drupal\Core\StringTranslation\TranslationInterface::formatPlural(), so
    // it tests for fragments of text.
    $indexed = $total - $remaining;
    $percent = ($total > 0) ? floor(100 * $indexed / $total) : 100;
    $this->drupalGet('admin/config/search/pages');
    $this->assertText($percent . '% of the site has been indexed.', 'Progress percent text at top of Search settings page is correct at: ' . $message);
    $this->assertText($remaining . ' item', 'Remaining text at top of Search settings page is correct at: ' . $message);

    // Check text in pages section of Search settings page.
    $this->assertText($indexed . ' of ' . $total . ' indexed', 'Progress text in pages section of Search settings page is correct at: ' . $message);

    // Check text on status report page.
    $this->drupalGet('admin/reports/status');
    $this->assertText('Search index progress', 'Search status section header is present on status report page');
    $this->assertText($percent . '%', 'Correct percentage is shown on status report page at: ' . $message);
    $this->assertText('(' . $remaining . ' remaining)', 'Correct remaining value is shown on status report page at: ' . $message);
  }

  /**
   * Checks actual database counts of items in the search index.
   *
   * @param int $count_node
   *   Count of node items to assert.
   * @param int $count_foo
   *   Count of "foo" items to assert.
   * @param string $message
   *   Message suffix to use.
   */
  protected function assertDatabaseCounts($count_node, $count_foo, $message) {
    // Count number of distinct nodes by ID.
    $results = db_select('search_dataset', 'i')
      ->fields('i', array('sid'))
      ->condition('type', 'node_search')
      ->groupBy('sid')
      ->execute()
      ->fetchCol();
    $this->assertEqual($count_node, count($results), 'Node count was ' . $count_node . ' for ' . $message);

    // Count number of "foo" records.
    $results = db_select('search_dataset', 'i')
      ->fields('i', array('sid'))
      ->condition('type', 'foo')
      ->execute()
      ->fetchCol();
    $this->assertEqual($count_foo, count($results), 'Foo count was ' . $count_foo . ' for ' . $message);

  }
}

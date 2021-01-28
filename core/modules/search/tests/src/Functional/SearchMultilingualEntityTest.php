<?php

namespace Drupal\Tests\search\Functional;

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search\SearchIndexInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests entities with multilingual fields.
 *
 * @group search
 */
class SearchMultilingualEntityTest extends BrowserTestBase {

  /**
   * List of searchable nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $searchableNodes = [];

  /**
   * Node search plugin.
   *
   * @var \Drupal\node\Plugin\Search\NodeSearch
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'locale',
    'comment',
    'node',
    'search',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a user who can administer search, do searches, see the status
    // report, and administer cron. Log in.
    $user = $this->drupalCreateUser([
      'administer search',
      'search content',
      'use advanced search',
      'access content',
      'access site reports',
      'administer site configuration',
    ]);
    $this->drupalLogin($user);

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
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();

    // Create a few page nodes with multilingual body values.
    $default_format = filter_default_format();
    $nodes = [
      [
        'title' => 'First node en',
        'type' => 'page',
        'body' => [['value' => $this->randomMachineName(32), 'format' => $default_format]],
        'langcode' => 'en',
      ],
      [
        'title' => 'Second node this is the English title',
        'type' => 'page',
        'body' => [['value' => $this->randomMachineName(32), 'format' => $default_format]],
        'langcode' => 'en',
      ],
      [
        'title' => 'Third node en',
        'type' => 'page',
        'body' => [['value' => $this->randomMachineName(32), 'format' => $default_format]],
        'langcode' => 'en',
      ],
      // After the third node, we don't care what the settings are. But we
      // need to have at least 5 to make sure the throttling is working
      // correctly. So, let's make 8 total.
      [],
      [],
      [],
      [],
      [],
    ];
    $this->searchableNodes = [];
    foreach ($nodes as $setting) {
      $this->searchableNodes[] = $this->drupalCreateNode($setting);
    }

    // Add a single translation to the second node.
    $translation = $this->searchableNodes[1]->addTranslation('hu', ['title' => 'Second node hu']);
    $translation->body->value = $this->randomMachineName(32);
    $this->searchableNodes[1]->save();

    // Add two translations to the third node.
    $translation = $this->searchableNodes[2]->addTranslation('hu', ['title' => 'Third node this is the Hungarian title']);
    $translation->body->value = $this->randomMachineName(32);
    $translation = $this->searchableNodes[2]->addTranslation('sv', ['title' => 'Third node sv']);
    $translation->body->value = $this->randomMachineName(32);
    $this->searchableNodes[2]->save();

    // Verify that we have 8 nodes left to do.
    $this->assertIndexCounts(8, 8, 'before updating the search index');
    $this->assertDatabaseCounts(0, 0, 'before updating the search index');
  }

  /**
   * Tests the indexing throttle and search results with multilingual nodes.
   */
  public function testMultilingualSearch() {
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
    $search_index = \Drupal::service('search.index');
    assert($search_index instanceof SearchIndexInterface);
    $this->assertIndexCounts(6, 8, 'after updating partially');
    $this->assertDatabaseCounts(2, 0, 'after updating partially');

    // Now index the rest of the nodes.
    // Make sure index throttle is high enough, via the UI.
    $this->drupalPostForm('admin/config/search/pages', ['cron_limit' => 20], 'Save configuration');
    $this->assertEqual(20, $this->config('search.settings')->get('index.cron_limit', 100), 'Config setting was saved correctly');
    // Get a new search plugin, to make sure it has this setting.
    $this->plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');

    $this->plugin->updateIndex();
    $this->assertIndexCounts(0, 8, 'after updating fully');
    $this->assertDatabaseCounts(8, 0, 'after updating fully');

    // Click the reindex button on the admin page, verify counts, and reindex.
    $this->drupalPostForm('admin/config/search/pages', [], 'Re-index site');
    $this->submitForm([], 'Re-index site');
    $this->assertIndexCounts(8, 8, 'after reindex');
    $this->assertDatabaseCounts(8, 0, 'after reindex');
    $this->plugin->updateIndex();

    // Test search results.

    // This should find two results for the second and third node.
    $this->plugin->setSearch('English OR Hungarian', [], []);
    $search_result = $this->plugin->execute();
    $this->assertCount(2, $search_result, 'Found two results.');
    // Nodes are saved directly after each other and have the same created time
    // so testing for the order is not possible.
    $results = [$search_result[0]['title'], $search_result[1]['title']];
    $this->assertContains('Third node this is the Hungarian title', $results, 'The search finds the correct Hungarian title.');
    $this->assertContains('Second node this is the English title', $results, 'The search finds the correct English title.');

    // Now filter for Hungarian results only.
    $this->plugin->setSearch('English OR Hungarian', ['f' => ['language:hu']], []);
    $search_result = $this->plugin->execute();

    $this->assertCount(1, $search_result, 'The search found only one result');
    $this->assertEqual('Third node this is the Hungarian title', $search_result[0]['title'], 'The search finds the correct Hungarian title.');

    // Test for search with common key word across multiple languages.
    $this->plugin->setSearch('node', [], []);
    $search_result = $this->plugin->execute();

    $this->assertCount(6, $search_result, 'The search found total six results');

    // Test with language filters and common key word.
    $this->plugin->setSearch('node', ['f' => ['language:hu']], []);
    $search_result = $this->plugin->execute();

    $this->assertCount(2, $search_result, 'The search found 2 results');

    // Test to check for the language of result items.
    foreach ($search_result as $result) {
      $this->assertEqual('hu', $result['langcode'], 'The search found the correct Hungarian result');
    }

    // Mark one of the nodes for reindexing, using the API function, and
    // verify indexing status.
    $search_index->markForReindex('node_search', $this->searchableNodes[0]->id());
    $this->assertIndexCounts(1, 8, 'after marking one node to reindex via API function');

    // Update the index and verify the totals again.
    $this->plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    $this->plugin->updateIndex();
    $this->assertIndexCounts(0, 8, 'after indexing again');

    // Mark one node for reindexing by saving it, and verify indexing status.
    $this->searchableNodes[1]->save();
    $this->assertIndexCounts(1, 8, 'after marking one node to reindex via save');

    // The request time is always the same throughout test runs. Update the
    // request time to a previous time, to simulate it having been marked
    // previously.
    $current = REQUEST_TIME;
    $old = $current - 10;
    $connection = Database::getConnection();
    $connection->update('search_dataset')
      ->fields(['reindex' => $old])
      ->condition('reindex', $current, '>=')
      ->execute();

    // Save the node again. Verify that the request time on it is not updated.
    $this->searchableNodes[1]->save();
    $result = $connection->select('search_dataset', 'd')
      ->fields('d', ['reindex'])
      ->condition('type', 'node_search')
      ->condition('sid', $this->searchableNodes[1]->id())
      ->execute()
      ->fetchField();
    $this->assertEqual($old, $result, 'Reindex time was not updated if node was already marked');

    // Add a bogus entry to the search index table using a different search
    // type. This will not appear in the index status, because it is not
    // managed by a plugin.
    $search_index->index('foo', $this->searchableNodes[0]->id(), 'en', 'some text');
    $this->assertIndexCounts(1, 8, 'after adding a different index item');

    // Mark just this "foo" index for reindexing.
    $search_index->markForReindex('foo');
    $this->assertIndexCounts(1, 8, 'after reindexing the other search type');

    // Mark everything for reindexing.
    $search_index->markForReindex();
    $this->assertIndexCounts(8, 8, 'after reindexing everything');

    // Clear one item from the index, but with wrong language.
    $this->assertDatabaseCounts(8, 1, 'before clear');
    $search_index->clear('node_search', $this->searchableNodes[0]->id(), 'hu');
    $this->assertDatabaseCounts(8, 1, 'after clear with wrong language');
    // Clear using correct language.
    $search_index->clear('node_search', $this->searchableNodes[0]->id(), 'en');
    $this->assertDatabaseCounts(7, 1, 'after clear with right language');
    // Don't specify language.
    $search_index->clear('node_search', $this->searchableNodes[1]->id());
    $this->assertDatabaseCounts(6, 1, 'unspecified language clear');
    // Clear everything in 'foo'.
    $search_index->clear('foo');
    $this->assertDatabaseCounts(6, 0, 'other index clear');
    // Clear everything.
    $search_index->clear();
    $this->assertDatabaseCounts(0, 0, 'complete clear');
  }

  /**
   * Verifies the indexing status counts.
   *
   * @param int $remaining
   *   Count of remaining items to verify.
   * @param int $total
   *   Count of total items to verify.
   * @param string $message
   *   Message to use, something like "after updating the search index".
   */
  protected function assertIndexCounts($remaining, $total, $message) {
    // Check status via plugin method call.
    $status = $this->plugin->indexStatus();
    $this->assertEqual($remaining, $status['remaining'], 'Remaining items ' . $message . ' is ' . $remaining);
    $this->assertEqual($total, $status['total'], 'Total items ' . $message . ' is ' . $total);

    // Check text in progress section of Search settings page. Note that this
    // test avoids using
    // \Drupal\Core\StringTranslation\TranslationInterface::formatPlural(), so
    // it tests for fragments of text.
    $indexed = $total - $remaining;
    $percent = ($total > 0) ? floor(100 * $indexed / $total) : 100;
    $this->drupalGet('admin/config/search/pages');
    $this->assertText($percent . '% of the site has been indexed.');
    $this->assertText($remaining . ' item');

    // Check text in pages section of Search settings page.
    $this->assertText($indexed . ' of ' . $total . ' indexed');

    // Check text on status report page.
    $this->drupalGet('admin/reports/status');
    $this->assertText('Search index progress');
    $this->assertText($percent . '%');
    $this->assertText('(' . $remaining . ' remaining)');
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
    $connection = Database::getConnection();
    $results = $connection->select('search_dataset', 'i')
      ->fields('i', ['sid'])
      ->condition('type', 'node_search')
      ->groupBy('sid')
      ->execute()
      ->fetchCol();
    $this->assertCount($count_node, $results, 'Node count was ' . $count_node . ' for ' . $message);

    // Count number of "foo" records.
    $results = $connection->select('search_dataset', 'i')
      ->fields('i', ['sid'])
      ->condition('type', 'foo')
      ->execute()
      ->fetchCol();
    $this->assertCount($count_foo, $results, 'Foo count was ' . $count_foo . ' for ' . $message);

  }

}

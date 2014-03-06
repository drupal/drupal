<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchMultilingualEntityTest.
 */

namespace Drupal\search\Tests;

use Drupal\Core\Language\Language;
use Drupal\field\Field;

/**
 * Tests entities with multilingual fields.
 */
class SearchMultilingualEntityTest extends SearchTestBase {

  /**
   * List of searchable nodes.
   *
   * @var array
   */
  protected $searchable_nodes = array();

  public static $modules = array('language', 'locale', 'comment');

  public static function getInfo() {
    return array(
      'name' => 'Multilingual entities',
      'description' => 'Tests entities with multilingual fields.',
      'group' => 'Search',
    );
  }

  function setUp() {
    parent::setUp();

    // Add two new languages.
    $language = new Language(array(
      'id' => 'hu',
      'name' => 'Hungarian',
    ));
    language_save($language);

    $language = new Language(array(
      'id' => 'sv',
      'name' => 'Swedish',
    ));
    language_save($language);

    // Make the body field translatable. The title is already translatable by
    // definition. The parent class has already created the article and page
    // content types.
    $field = Field::fieldInfo()->getField('node', 'body');
    $field->translatable = TRUE;
    $field->save();

    // Create a few page nodes with multilingual body values.
    $default_format = filter_default_format();
    $nodes = array(
      array(
        'title' => 'First node en',
        'type' => 'page',
        'body' => array(array('value' => $this->randomName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
      array(
        'title' => 'Second node this is the English title',
        'type' => 'page',
        'body' => array(array('value' => $this->randomName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
      array(
        'title' => 'Third node en',
        'type' => 'page',
        'body' => array(array('value' => $this->randomName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
    );
    $this->searchable_nodes = array();
    foreach ($nodes as $setting) {
      $this->searchable_nodes[] = $this->drupalCreateNode($setting);
    }
    // Add a single translation to the second node.

    $translation = $this->searchable_nodes[1]->addTranslation('hu', array('title' => 'Second node hu'));
    $translation->body->value = $this->randomName(32);
    $this->searchable_nodes[1]->save();

    // Add two translations to the third node.
    $translation = $this->searchable_nodes[2]->addTranslation('hu', array('title' => 'Third node this is the Hungarian title'));
    $translation->body->value = $this->randomName(32);
    $translation = $this->searchable_nodes[2]->addTranslation('sv', array('title' => 'Third node sv'));
    $translation->body->value = $this->randomName(32);
    $this->searchable_nodes[2]->save();
  }

  /**
   * Tests for indexing throttle with nodes in multiple languages.
   */
  function testIndexingThrottle() {
    // Index only 4 items per cron run.
    \Drupal::config('search.settings')->set('index.cron_limit', 4)->save();
    // Update the index. This does the initial processing.
    $plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    $plugin->updateIndex();
    // Run the shutdown function. Testing is a unique case where indexing
    // and searching has to happen in the same request, so running the shutdown
    // function manually is needed to finish the indexing process.
    search_update_totals();
    // Then check how many nodes have been indexed. We have created three nodes,
    // the first has one, the second has two and the third has three language
    // variants. Indexing the third would exceed the throttle limit, so we
    // expect that only the first two will be indexed.
    $status = $plugin->indexStatus();
    $this->assertEqual($status['remaining'], 1, 'Remaining items after updating the search index is 1.');
  }

  /**
   * Tests searching nodes with multiple languages.
   */
  function testSearchingMultilingualFieldValues() {
    // Update the index and then run the shutdown method.
    // See testIndexingThrottle() for further explanation.
    $plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    $plugin->updateIndex();
    search_update_totals();

    // This should find two results for the second and third node.
    $plugin->setSearch('English OR Hungarian', array(), array());
    $search_result = $plugin->execute();

    $this->assertEqual($search_result[0]['title'], 'Third node this is the Hungarian title', 'The search finds the correct Hungarian title.');
    $this->assertEqual($search_result[1]['title'], 'Second node this is the English title', 'The search finds the correct English title.');

    // Now filter for Hungarian results only.
    $plugin->setSearch('English OR Hungarian', array('f' => array('language:hu')), array());
    $search_result = $plugin->execute();

    $this->assertEqual(count($search_result), 1, 'The search found only one result');
    $this->assertEqual($search_result[0]['title'], 'Third node this is the Hungarian title', 'The search finds the correct Hungarian title.');

    // Test for search with common key word across multiple languages.
    $plugin->setSearch('node', array(), array());
    $search_result = $plugin->execute();

    $this->assertEqual(count($search_result), 6, 'The search found total six results');

    // Test with language filters and common key word.
    $plugin->setSearch('node', array('f' => array('language:hu')), array());
    $search_result = $plugin->execute();

    $this->assertEqual(count($search_result), 2, 'The search found 2 results');

    // Test to check for the language of result items.
    foreach($search_result as $result) {
      $this->assertEqual($result['langcode'], 'hu', 'The search found the correct Hungarian result');
    }
  }
}

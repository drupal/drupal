<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchMultilingualEntityTest.
 */

namespace Drupal\search\Tests;

use Drupal\Core\Language\Language;

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

    // Make the body field translatable.
    // The parent class has already created the article and page content types.
    $field = field_info_field('body');
    $field->translatable = TRUE;
    $field->save();

    // Create a few page nodes with multilingual body values.
    $default_format = filter_default_format();
    $nodes = array(
      array(
        'type' => 'page',
        'body' => array(array('value' => $this->randomName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
      array(
        'type' => 'page',
        'body' => array(array('value' => $this->randomName(32), 'format' => $default_format)),
        'langcode' => 'en',
      ),
      array(
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
    $translation = $this->searchable_nodes[1]->getTranslation('hu');
    $translation->body->value = $this->randomName(32);
    $this->searchable_nodes[1]->save();

    // Add two translations to the third node.
    $translation = $this->searchable_nodes[2]->getTranslation('hu');
    $translation->body->value = $this->randomName(32);
    $translation = $this->searchable_nodes[2]->getTranslation('sv');
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
    node_update_index();
    // Run the shutdown function. Testing is a unique case where indexing
    // and searching has to happen in the same request, so running the shutdown
    // function manually is needed to finish the indexing process.
    search_update_totals();
    // Then check how many nodes have been indexed. We have created three nodes,
    // the first has one, the second has two and the third has three language
    // variants. Indexing the third would exceed the throttle limit, so we
    // expect that only the first two will be indexed.
    $status = module_invoke('node', 'search_status');
    $this->assertEqual($status['remaining'], 1, 'Remaining items after updating the search index is 1.');
  }

  /**
   * Tests searching nodes with multiple languages.
   */
  function testSearchingMultilingualFieldValues() {
    // Update the index and then run the shutdown method.
    // See testIndexingThrottle() for further explanation.
    node_update_index();
    search_update_totals();
    foreach ($this->searchable_nodes as $node) {
      // Each searchable node that we created contains values in the body field
      // in one or more languages. Let's pick the last language variant from the
      // body array and execute a search using that as a search keyword.
      $body_language_variant = end($node->body);
      $search_result = node_search_execute($body_language_variant[0]['value']);
      // See whether we get the same node as a result.
      $this->assertEqual($search_result[0]['node']->id(), $node->id(), 'The search has resulted the correct node.');
    }
  }
}

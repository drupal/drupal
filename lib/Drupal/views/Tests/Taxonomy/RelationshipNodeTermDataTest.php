<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Taxonomy\RelationshipNodeTermDataTest.
 */

namespace Drupal\views\Tests\Taxonomy;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the node_term_data relationship handler.
 */
class RelationshipNodeTermDataTest extends ViewTestBase {

  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy: Node term data Relationship',
      'description' => 'Tests the taxonomy term on node relationship handler.',
      'group' => 'Views Modules',
    );
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  function createTerm($vocabulary) {
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      // Use the first available text format.
      'format' => db_query_range('SELECT format FROM {filter_format}', 0, 1)->fetchField(),
      'vid' => $vocabulary->vid,
      'langcode' => LANGUAGE_NOT_SPECIFIED,
    ));
    taxonomy_term_save($term);
    return $term;
  }

  function setUp() {
    parent::setUp();

    $vocabulary = taxonomy_vocabulary_machine_name_load('tags');
    $this->term_1 = $this->createTerm($vocabulary);
    $this->term_2 = $this->createTerm($vocabulary);

    $node = array();
    $node['type'] = 'article';
    $node['field_tags'][LANGUAGE_NOT_SPECIFIED][]['tid'] = $this->term_1->tid;
    $node['field_tags'][LANGUAGE_NOT_SPECIFIED][]['tid'] = $this->term_2->tid;
    $this->node = $this->drupalCreateNode($node);
  }

  function testViewsHandlerRelationshipNodeTermData() {
    $this->executeView($this->view, array($this->term_1->tid, $this->term_2->tid));
    $resultset = array(
      array(
        'nid' => $this->node->nid,
      ),
    );
    $this->column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($this->view, $resultset, $this->column_map);
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::getBasicView().
   */
  protected function getBasicView() {
    return $this->createViewFromConfig('test_taxonomy_node_term_data');
  }

}

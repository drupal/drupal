<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Taxonomy\RelationshipNodeTermDataTest.
 */

namespace Drupal\views\Tests\Taxonomy;

use Drupal\views\Tests\ViewsSqlTest;
use Drupal\views\View;

/**
 * Tests the node_term_data relationship handler.
 */
class RelationshipNodeTermDataTest extends ViewsSqlTest {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Tests handler relationship_node_term_data',
      'description' => 'Tests the taxonomy term on node relationship handler',
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
    $view = $this->view_taxonomy_node_term_data();

    $this->executeView($view, array($this->term_1->tid, $this->term_2->tid));
    $resultset = array(
      array(
        'nid' => $this->node->nid,
      ),
    );
    $this->column_map = array('nid' => 'nid');
    debug($view->result);
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }

  function view_taxonomy_node_term_data() {
    $view = new View();
    $view->name = 'test_taxonomy_node_term_data';
    $view->description = '';
    $view->tag = '';
    $view->base_table = 'node';
    $view->human_name = 'test_taxonomy_node_term_data';
    $view->core = 7;
    $view->api_version = '3.0';
    $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

    /* Display: Master */
    $handler = $view->new_display('default', 'Master', 'default');
    $handler->display->display_options['access']['type'] = 'perm';
    $handler->display->display_options['cache']['type'] = 'none';
    $handler->display->display_options['query']['type'] = 'views_query';
    $handler->display->display_options['exposed_form']['type'] = 'basic';
    $handler->display->display_options['pager']['type'] = 'full';
    $handler->display->display_options['style_plugin'] = 'default';
    $handler->display->display_options['row_plugin'] = 'node';
    /* Relationship: Content: Taxonomy terms on node */
    $handler->display->display_options['relationships']['term_node_tid']['id'] = 'term_node_tid';
    $handler->display->display_options['relationships']['term_node_tid']['table'] = 'node';
    $handler->display->display_options['relationships']['term_node_tid']['field'] = 'term_node_tid';
    $handler->display->display_options['relationships']['term_node_tid']['label'] = 'Term #1';
    $handler->display->display_options['relationships']['term_node_tid']['vocabularies'] = array(
      'tags' => 0,
    );
    /* Relationship: Content: Taxonomy terms on node */
    $handler->display->display_options['relationships']['term_node_tid_1']['id'] = 'term_node_tid_1';
    $handler->display->display_options['relationships']['term_node_tid_1']['table'] = 'node';
    $handler->display->display_options['relationships']['term_node_tid_1']['field'] = 'term_node_tid';
    $handler->display->display_options['relationships']['term_node_tid_1']['label'] = 'Term #2';
    $handler->display->display_options['relationships']['term_node_tid_1']['vocabularies'] = array(
      'tags' => 0,
    );
    /* Contextual filter: Taxonomy term: Term ID */
    $handler->display->display_options['arguments']['tid']['id'] = 'tid';
    $handler->display->display_options['arguments']['tid']['table'] = 'taxonomy_term_data';
    $handler->display->display_options['arguments']['tid']['field'] = 'tid';
    $handler->display->display_options['arguments']['tid']['relationship'] = 'term_node_tid';
    $handler->display->display_options['arguments']['tid']['default_argument_type'] = 'fixed';
    $handler->display->display_options['arguments']['tid']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['tid']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['tid']['summary_options']['items_per_page'] = '25';
    /* Contextual filter: Taxonomy term: Term ID */
    $handler->display->display_options['arguments']['tid_1']['id'] = 'tid_1';
    $handler->display->display_options['arguments']['tid_1']['table'] = 'taxonomy_term_data';
    $handler->display->display_options['arguments']['tid_1']['field'] = 'tid';
    $handler->display->display_options['arguments']['tid_1']['relationship'] = 'term_node_tid_1';
    $handler->display->display_options['arguments']['tid_1']['default_argument_type'] = 'fixed';
    $handler->display->display_options['arguments']['tid_1']['summary']['number_of_records'] = '0';
    $handler->display->display_options['arguments']['tid_1']['summary']['format'] = 'default_summary';
    $handler->display->display_options['arguments']['tid_1']['summary_options']['items_per_page'] = '25';

    return $view;
  }
}

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

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy');

  /**
   * The vocabulary for the test.
   *
   * @var Drupal\taxonomy\Vocabulary
   */
  protected $vocabulary;

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
  function createTerm() {
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      // Use the first available text format.
      'format' => db_query_range('SELECT format FROM {filter_format}', 0, 1)->fetchField(),
      'vid' => $this->vocabulary->vid,
      'langcode' => LANGUAGE_NOT_SPECIFIED,
    ));
    taxonomy_term_save($term);
    return $term;
  }

  function setUp() {
    parent::setUp();
    $this->mockStandardInstall();

    $this->term_1 = $this->createTerm();
    $this->term_2 = $this->createTerm();

    $node = array();
    $node['type'] = 'article';
    $node['field_views_testing_tags'][LANGUAGE_NOT_SPECIFIED][]['tid'] = $this->term_1->tid;
    $node['field_views_testing_tags'][LANGUAGE_NOT_SPECIFIED][]['tid'] = $this->term_2->tid;
    $this->node = $this->drupalCreateNode($node);
  }

  /**
   * Provides a workaround for the inability to use the standard profile.
   *
   * @see http://drupal.org/node/1708692
   */
  protected function mockStandardInstall() {
    $type = array(
      'type' => 'article',
    );

    $type = node_type_set_defaults($type);
    node_type_save($type);
    node_add_body_field($type);

    // Create the vocabulary for the tag field.
    $this->vocabulary = entity_create('taxonomy_vocabulary',  array(
      'name' => 'Views testing tags',
      'machine_name' => 'views_testing_tags',
    ));
    $this->vocabulary->save();
    $field = array(
      'field_name' => 'field_' . $this->vocabulary->machine_name,
      'type' => 'taxonomy_term_reference',
      // Set cardinality to unlimited for tagging.
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->machine_name,
            'parent' => 0,
          ),
        ),
      ),
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'field_' . $this->vocabulary->machine_name,
      'entity_type' => 'node',
      'label' => 'Tags',
      'bundle' => 'article',
      'widget' => array(
        'type' => 'taxonomy_autocomplete',
        'weight' => -4,
      ),
      'display' => array(
        'default' => array(
          'type' => 'taxonomy_term_reference_link',
          'weight' => 10,
        ),
        'teaser' => array(
          'type' => 'taxonomy_term_reference_link',
          'weight' => 10,
        ),
      ),
    );
    field_create_instance($instance);
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

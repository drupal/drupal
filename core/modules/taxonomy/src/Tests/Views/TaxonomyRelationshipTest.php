<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyRelationshipTest.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests taxonomy relationships with parent term and node.
 *
 * @group taxonomy
 */
class TaxonomyRelationshipTest extends TaxonomyTestBase {

  /**
   * Stores the terms used in the tests.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = array();

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_taxonomy_term_relationship');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Make term2 parent of term1.
    $this->term1->set('parent', $this->term2->id());
    $this->term1->save();
    // Store terms in an array for testing.
    $this->terms[] = $this->term1;
    $this->terms[] = $this->term2;
    // Only set term1 on node1 and term2 on node2 for testing.
    unset($this->nodes[0]->field_views_testing_tags[1]);
    $this->nodes[0]->save();
    unset($this->nodes[1]->field_views_testing_tags[0]);
    $this->nodes[1]->save();

    Views::viewsData()->clear();

  }

  /**
   * Tests the taxonomy parent plugin UI.
   */
  public function testTaxonomyRelationships() {

    // Check the generated views data of taxonomy_index.
    $views_data = Views::viewsData()->get('taxonomy_index');
    // Check the table join data.
    $this->assertEqual($views_data['table']['join']['taxonomy_term_field_data']['left_field'], 'tid');
    $this->assertEqual($views_data['table']['join']['taxonomy_term_field_data']['field'], 'tid');
    $this->assertEqual($views_data['table']['join']['node_field_data']['left_field'], 'nid');
    $this->assertEqual($views_data['table']['join']['node_field_data']['field'], 'nid');
    $this->assertEqual($views_data['table']['join']['taxonomy_term_hierarchy']['left_field'], 'tid');
    $this->assertEqual($views_data['table']['join']['taxonomy_term_hierarchy']['field'], 'tid');

    // Check the generated views data of taxonomy_term_hierarchy.
    $views_data = Views::viewsData()->get('taxonomy_term_hierarchy');
    // Check the table join data.
    $this->assertEqual($views_data['table']['join']['taxonomy_term_hierarchy']['left_field'], 'tid');
    $this->assertEqual($views_data['table']['join']['taxonomy_term_hierarchy']['field'], 'parent');
    $this->assertEqual($views_data['table']['join']['taxonomy_term_field_data']['left_field'], 'tid');
    $this->assertEqual($views_data['table']['join']['taxonomy_term_field_data']['field'], 'tid');
    // Check the parent relationship data.
    $this->assertEqual($views_data['parent']['relationship']['base'], 'taxonomy_term_field_data');
    $this->assertEqual($views_data['parent']['relationship']['field'], 'parent');
    $this->assertEqual($views_data['parent']['relationship']['label'], t('Parent'));
    $this->assertEqual($views_data['parent']['relationship']['id'], 'standard');
    // Check the parent filter and argument data.
    $this->assertEqual($views_data['parent']['filter']['id'], 'numeric');
    $this->assertEqual($views_data['parent']['argument']['id'], 'taxonomy');

    // Check an actual test view.
    $view = Views::getView('test_taxonomy_term_relationship');
    $this->executeView($view);
    /** @var \Drupal\views\ResultRow $row */
    foreach ($view->result as $index => $row) {
      // Check that the actual ID of the entity is the expected one.
      $this->assertEqual($row->tid, $this->terms[$index]->id());

      // Also check that we have the correct result entity.
      $this->assertEqual($row->_entity->id(), $this->terms[$index]->id());
      $this->assertTrue($row->_entity instanceof TermInterface);

      if (!$index) {
        $this->assertTrue($row->_relationship_entities['parent'] instanceof TermInterface);
        $this->assertEqual($row->_relationship_entities['parent']->id(), $this->term2->id());
        $this->assertEqual($row->taxonomy_term_field_data_taxonomy_term_hierarchy_tid, $this->term2->id());
      }
      $this->assertTrue($row->_relationship_entities['nid'] instanceof NodeInterface);
      $this->assertEqual($row->_relationship_entities['nid']->id(), $this->nodes[$index]->id());
    }
  }

}

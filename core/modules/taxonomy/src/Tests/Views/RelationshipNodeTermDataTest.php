<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\RelationshipNodeTermDataTest.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the node_term_data relationship handler.
 */
class RelationshipNodeTermDataTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_taxonomy_node_term_data');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy: Node term data Relationship',
      'description' => 'Tests the taxonomy term on node relationship handler.',
      'group' => 'Views module integration',
    );
  }

  function testViewsHandlerRelationshipNodeTermData() {
    $view = Views::getView('test_taxonomy_node_term_data');
    $this->executeView($view, array($this->term1->id(), $this->term2->id()));
    $resultset = array(
      array(
        'nid' => $this->nodes[0]->id(),
      ),
      array(
        'nid' => $this->nodes[1]->id(),
      ),
    );
    $this->column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }

}

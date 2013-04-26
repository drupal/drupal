<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\RelationshipNodeTermDataTest.
 */

namespace Drupal\taxonomy\Tests\Views;

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
    $view = views_get_view('test_taxonomy_node_term_data');
    $this->executeView($view, array($this->term1->id(), $this->term2->id()));
    $resultset = array(
      array(
        'nid' => $this->nodes[0]->nid,
      ),
      array(
        'nid' => $this->nodes[1]->nid,
      ),
    );
    $this->column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($view, $resultset, $this->column_map);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\RelationshipRepresentativeNode.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the representative node relationship for terms.
 */
class RelationshipRepresentativeNode extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_groupwise_term');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy: Representative Node Relationship',
      'description' => 'Tests the representative node relationship for terms.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the relationship.
   */
  public function testRelationship() {
    $view = Views::getView('test_groupwise_term');
    $this->executeView($view);
    $map = array('node_taxonomy_term_data_nid' => 'nid', 'tid' => 'tid');
    $expected_result = array(
      array(
        'nid' => $this->nodes[1]->id(),
        'tid' => $this->term2->id(),
      ),
      array(
        'nid' => $this->nodes[1]->id(),
        'tid' => $this->term1->id(),
      ),
    );
    $this->assertIdenticalResultset($view, $expected_result, $map);
  }
}

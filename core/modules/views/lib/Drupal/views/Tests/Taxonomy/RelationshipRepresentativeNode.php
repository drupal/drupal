<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Taxonomy\RelationshipRepresentativeNode.
 */

namespace Drupal\views\Tests\Taxonomy;

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
      'group' => 'Views Modules',
    );
  }

  /**
   * Tests the relationship.
   */
  public function testRelationship() {
    $view = views_get_view('test_groupwise_term');
    $this->executeView($view);
    $map = array('node_taxonomy_term_data_nid' => 'nid', 'tid' => 'tid');
    $expected_result = array(
      array(
        'nid' => $this->nodes[1]->nid,
        'tid' => $this->term2->tid,
      ),
      array(
        'nid' => $this->nodes[1]->nid,
        'tid' => $this->term1->tid,
      ),
    );
    $this->assertIdenticalResultset($view, $expected_result, $map);
  }
}

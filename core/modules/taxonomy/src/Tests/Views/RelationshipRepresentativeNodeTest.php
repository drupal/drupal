<?php

namespace Drupal\taxonomy\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the representative node relationship for terms.
 *
 * @group taxonomy
 */
class RelationshipRepresentativeNodeTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_groupwise_term');

  /**
   * Tests the relationship.
   */
  public function testRelationship() {
    $view = Views::getView('test_groupwise_term');
    $this->executeView($view);
    $map = array('node_field_data_taxonomy_term_field_data_nid' => 'nid', 'tid' => 'tid');
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

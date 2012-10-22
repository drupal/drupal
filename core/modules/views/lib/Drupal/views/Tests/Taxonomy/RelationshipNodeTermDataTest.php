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
class RelationshipNodeTermDataTest extends TaxonomyTestBase {

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

  function testViewsHandlerRelationshipNodeTermData() {
    $this->executeView($this->view, array($this->term1->tid, $this->term2->tid));
    $resultset = array(
      array(
        'nid' => $this->nodes[0]->nid,
      ),
      array(
        'nid' => $this->nodes[1]->nid,
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

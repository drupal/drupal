<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Node\RevisionRelationships.
 */
namespace Drupal\views\Tests\Node;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests basic node_revision table integration into views.
 */
class RevisionRelationships extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_node_revision_nid', 'test_node_revision_vid');

  public static function getInfo() {
    return array(
      'name' => 'Node: Revision integration',
      'description' => 'Tests the integration of node_revision table of node module',
      'group' => 'Views Modules',
    );
  }

  /**
   * Create a node with revision and rest result count for both views.
   */
  public function testNodeRevisionRelationship() {
    $node = $this->drupalCreateNode();
    // Create revision of the node.
    $node_revision = clone $node;
    $node_revision->setNewRevision();
    $node_revision->save();
    $column_map = array(
      'vid' => 'vid',
      'node_revision_nid' => 'node_revision_nid',
      'node_node_revision_nid' => 'node_node_revision_nid',
    );

    // Here should be two rows.
    $view_nid = views_get_view('test_node_revision_nid');
    $this->executeView($view_nid, array($node->nid));
    $resultset_nid = array(
      array(
        'vid' => '1',
        'node_revision_nid' => '1',
        'node_node_revision_nid' => '1',
      ),
      array(
        'vid' => '2',
        'node_revision_nid' => '1',
        'node_node_revision_nid' => '1',
      ),
    );
    $this->assertIdenticalResultset($view_nid, $resultset_nid, $column_map);

    // There should be only one row with active revision 2.
    $view_vid = views_get_view('test_node_revision_vid');
    $this->executeView($view_vid, array($node->nid));
    $resultset_vid = array(
      array(
        'vid' => '2',
        'node_revision_nid' => '1',
        'node_node_revision_nid' => '1',
      ),
    );
    $this->assertIdenticalResultset($view_vid, $resultset_vid, $column_map);
  }

}

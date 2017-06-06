<?php

namespace Drupal\Tests\node\Kernel\Views;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the integration of node_revision table of node module.
 *
 * @group node
 */
class RevisionRelationshipsTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node' , 'node_test_views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installSchema('node', 'node_access');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    ViewTestData::createTestViews(get_class($this), ['node_test_views']);
  }

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_revision_nid', 'test_node_revision_vid'];

  /**
   * Create a node with revision and rest result count for both views.
   */
  public function testNodeRevisionRelationship() {
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();
    $node = Node::create(['type' => 'page', 'title' => 'test', 'uid' => 1]);
    $node->save();
    // Create revision of the node.
    $node->setNewRevision(TRUE);
    $node->save();
    $column_map = [
      'vid' => 'vid',
      'node_field_data_node_field_revision_nid' => 'node_node_revision_nid',
      'nid_1' => 'nid_1',
    ];

    // Here should be two rows.
    $view_nid = Views::getView('test_node_revision_nid');
    $this->executeView($view_nid, [$node->id()]);
    $resultset_nid = [
      [
        'vid' => '1',
        'node_node_revision_nid' => '1',
        'nid_1' => '1',
      ],
      [
        'vid' => '2',
        'node_revision_nid' => '1',
        'node_node_revision_nid' => '1',
        'nid_1' => '1',
      ],
    ];
    $this->assertIdenticalResultset($view_nid, $resultset_nid, $column_map);

    // There should be only one row with active revision 2.
    $view_vid = Views::getView('test_node_revision_vid');
    $this->executeView($view_vid, [$node->id()]);
    $resultset_vid = [
      [
        'vid' => '2',
        'node_node_revision_nid' => '1',
        'nid_1' => '1',
      ],
    ];
    $this->assertIdenticalResultset($view_vid, $resultset_vid, $column_map);
  }

}

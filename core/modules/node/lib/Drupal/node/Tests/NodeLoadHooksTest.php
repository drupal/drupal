<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeLoadHooksTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests for the hooks invoked during node_load().
 */
class NodeLoadHooksTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_test');

  public static function getInfo() {
    return array(
      'name' => 'Node load hooks',
      'description' => 'Test the hooks invoked when a node is being loaded.',
      'group' => 'Node',
    );
  }

  /**
   * Tests that hook_node_load() is invoked correctly.
   */
  function testHookNodeLoad() {
    // Create some sample articles and pages.
    $node1 = $this->drupalCreateNode(array('type' => 'article', 'status' => NODE_PUBLISHED));
    $node2 = $this->drupalCreateNode(array('type' => 'article', 'status' => NODE_PUBLISHED));
    $node3 = $this->drupalCreateNode(array('type' => 'article', 'status' => NODE_NOT_PUBLISHED));
    $node4 = $this->drupalCreateNode(array('type' => 'page', 'status' => NODE_NOT_PUBLISHED));

    // Check that when a set of nodes that only contains articles is loaded,
    // the properties added to the node by node_test_load_node() correctly
    // reflect the expected values.
    $nodes = entity_load_multiple_by_properties('node', array('status' => NODE_PUBLISHED));
    $loaded_node = end($nodes);
    $this->assertEqual($loaded_node->node_test_loaded_nids, array($node1->id(), $node2->id()), 'hook_node_load() received the correct list of node IDs the first time it was called.');
    $this->assertEqual($loaded_node->node_test_loaded_types, array('article'), 'hook_node_load() received the correct list of node types the first time it was called.');

    // Now, as part of the same page request, load a set of nodes that contain
    // both articles and pages, and make sure the parameters passed to
    // node_test_node_load() are correctly updated.
    $nodes = entity_load_multiple_by_properties('node', array('status' => NODE_NOT_PUBLISHED));
    $loaded_node = end($nodes);
    $this->assertEqual($loaded_node->node_test_loaded_nids, array($node3->id(), $node4->id()), 'hook_node_load() received the correct list of node IDs the second time it was called.');
    $this->assertEqual($loaded_node->node_test_loaded_types, array('article', 'page'), 'hook_node_load() received the correct list of node types the second time it was called.');
  }
}

<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeBuildContentTest.
 */

namespace Drupal\node\Tests;

/**
 * Test to ensure that a node's content is always rebuilt.
 */
class NodeBuildContentTest extends NodeTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Rebuild content',
      'description' => 'Test the rebuilding of content for different build modes.',
      'group' => 'Node',
    );
  }

 /**
  * Ensures that content array is rebuilt on every call to node_build_content().
  */
  function testNodeRebuildContent() {
    $node = $this->drupalCreateNode();

    // Set a property in the content array so we can test for its existence later on.
    $node->content['test_content_property'] = array(
      '#value' => $this->randomString(),
    );
    $content = node_view($node);

    // If the property doesn't exist it means the node->content was rebuilt.
    $this->assertFalse(isset($content['test_content_property']), 'Node content was emptied prior to being built.');
  }
}

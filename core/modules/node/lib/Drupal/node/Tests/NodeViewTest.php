<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeViewTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the node/{node} page.
 *
 * @see \Drupal\node\Controller\NodeController
 */
class NodeViewTest extends NodeTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Node view page',
      'description' => 'Tests the node/{node} page.',
      'group' => 'Node',
    );
  }

  /**
   * Tests the html head links.
   */
  public function testHtmlHeadLinks() {
    $node = $this->drupalCreateNode();

    $this->drupalGet($node->getSystemPath());

    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertEqual($result[0]['href'], url("node/{$node->id()}/revisions"));

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEqual($result[0]['href'], url("node/{$node->id()}/edit"));

    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($result[0]['href'], url("node/{$node->id()}"));
  }

}

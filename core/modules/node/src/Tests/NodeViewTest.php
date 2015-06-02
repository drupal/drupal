<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeViewTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the node/{node} page.
 *
 * @group node
 * @see \Drupal\node\Controller\NodeController
 */
class NodeViewTest extends NodeTestBase {

  /**
   * Tests the html head links.
   */
  public function testHtmlHeadLinks() {
    $node = $this->drupalCreateNode();

    $this->drupalGet($node->urlInfo());

    $result = $this->xpath('//link[@rel = "version-history"]');
    $this->assertEqual($result[0]['href'], $node->url('version-history'));

    $result = $this->xpath('//link[@rel = "edit-form"]');
    $this->assertEqual($result[0]['href'], $node->url('edit-form'));

    $result = $this->xpath('//link[@rel = "canonical"]');
    $this->assertEqual($result[0]['href'], $node->url());
  }

}

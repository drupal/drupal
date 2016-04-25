<?php

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

  /**
   * Tests that we store and retrieve multi-byte UTF-8 characters correctly.
   */
  public function testMultiByteUtf8() {
    $title = '🐝';
    $this->assertTrue(mb_strlen($title, 'utf-8') < strlen($title), 'Title has multi-byte characters.');
    $node = $this->drupalCreateNode(array('title' => $title));
    $this->drupalGet($node->urlInfo());
    $result = $this->xpath('//span[contains(@class, "field--name-title")]');
    $this->assertEqual((string) $result[0], $title, 'The passed title was returned.');
  }

}

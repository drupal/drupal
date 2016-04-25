<?php

namespace Drupal\node\Tests;

/**
 * Tests the output of node links (read more, add new comment, etc).
 *
 * @group node
 */
class NodeLinksTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views');

  /**
   * Tests that the links can be hidden in the view display settings.
   */
  public function testHideLinks() {
    $node = $this->drupalCreateNode(array(
      'type' => 'article',
      'promote' => NODE_PROMOTED,
    ));

    // Links are displayed by default.
    $this->drupalGet('node');
    $this->assertText($node->getTitle());
    $this->assertLink('Read more');

    // Hide links.
    entity_get_display('node', 'article', 'teaser')
      ->removeComponent('links')
      ->save();

    $this->drupalGet('node');
    $this->assertText($node->getTitle());
    $this->assertNoLink('Read more');
  }

}

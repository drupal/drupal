<?php

namespace Drupal\Tests\node\Functional;

use Drupal\node\NodeInterface;

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
  protected static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the links can be hidden in the view display settings.
   */
  public function testHideLinks() {
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'promote' => NodeInterface::PROMOTED,
    ]);

    // Links are displayed by default.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($node->getTitle());
    $this->assertSession()->linkExists('Read more');

    // Hide links.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article', 'teaser')
      ->removeComponent('links')
      ->save();

    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($node->getTitle());
    $this->assertSession()->linkNotExists('Read more');
  }

}

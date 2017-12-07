<?php

namespace Drupal\Tests\node\Functional;

use Drupal\node\Entity\Node;

/**
 * Tests views contextual links on nodes.
 *
 * @group node
 */
class NodeContextualLinksTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'contextual',
  ];

  /**
   * Tests contextual links.
   */
  public function testNodeContextualLinks() {
    // Create a node item.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Unnamed',
    ]);
    $node->save();

    $user = $this->drupalCreateUser([
      'administer nodes',
      'access contextual links',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementAttributeContains('css', 'div[data-contextual-id]', 'data-contextual-id', 'node:node=' . $node->id() . ':');
  }

}

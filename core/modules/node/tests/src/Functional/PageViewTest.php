<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\node\Entity\Node;

/**
 * Create a node and test edit permissions.
 *
 * @group node
 */
class PageViewTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests editing a node by users with various access permissions.
   */
  public function testPageView(): void {
    // Create a node to view.
    $node = $this->drupalCreateNode();
    $this->assertNotEmpty(Node::load($node->id()), 'Node created.');

    // Try to edit with anonymous user.
    $this->drupalGet("node/" . $node->id() . "/edit");
    $this->assertSession()->statusCodeEquals(403);

    // Create a user without permission to edit node.
    $web_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($web_user);

    // Attempt to access edit page.
    $this->drupalGet("node/" . $node->id() . "/edit");
    $this->assertSession()->statusCodeEquals(403);

    // Create user with permission to edit node.
    $web_user = $this->drupalCreateUser(['bypass node access']);
    $this->drupalLogin($web_user);

    // Attempt to access edit page.
    $this->drupalGet("node/" . $node->id() . "/edit");
    $this->assertSession()->statusCodeEquals(200);
  }

}

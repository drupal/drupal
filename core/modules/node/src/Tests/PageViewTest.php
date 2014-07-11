<?php

/**
 * @file
 * Definition of Drupal\node\Tests\PageViewTest.
 */

namespace Drupal\node\Tests;

/**
 * Create a node and test edit permissions.
 *
 * @group node
 */
class PageViewTest extends NodeTestBase {
  /**
   * Tests an anonymous and unpermissioned user attempting to edit the node.
   */
  function testPageView() {
    // Create a node to view.
    $node = $this->drupalCreateNode();
    $this->assertTrue(node_load($node->id()), 'Node created.');

    // Try to edit with anonymous user.
    $this->drupalGet("node/" . $node->id() . "/edit");
    $this->assertResponse(403);

    // Create a user without permission to edit node.
    $web_user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($web_user);

    // Attempt to access edit page.
    $this->drupalGet("node/" . $node->id() . "/edit");
    $this->assertResponse(403);

    // Create user with permission to edit node.
    $web_user = $this->drupalCreateUser(array('bypass node access'));
    $this->drupalLogin($web_user);

    // Attempt to access edit page.
    $this->drupalGet("node/" . $node->id() . "/edit");
    $this->assertResponse(200);
  }
}

<?php

/**
 * @file
 * Definition of Drupal\node\Tests\PageViewTest.
 */

namespace Drupal\node\Tests;

class PageViewTest extends NodeTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Node edit permissions',
      'description' => 'Create a node and test edit permissions.',
      'group' => 'Node',
    );
  }

  /**
   * Creates a node and then an anonymous and unpermissioned user attempt to edit the node.
   */
  function testPageView() {
    // Create a node to view.
    $node = $this->drupalCreateNode();
    $this->assertTrue(node_load($node->nid), t('Node created.'));

    // Try to edit with anonymous user.
    $html = $this->drupalGet("node/$node->nid/edit");
    $this->assertResponse(403);

    // Create a user without permission to edit node.
    $web_user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($web_user);

    // Attempt to access edit page.
    $this->drupalGet("node/$node->nid/edit");
    $this->assertResponse(403);

    // Create user with permission to edit node.
    $web_user = $this->drupalCreateUser(array('bypass node access'));
    $this->drupalLogin($web_user);

    // Attempt to access edit page.
    $this->drupalGet("node/$node->nid/edit");
    $this->assertResponse(200);
  }
}

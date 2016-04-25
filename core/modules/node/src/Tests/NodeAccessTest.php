<?php

namespace Drupal\node\Tests;

use Drupal\user\RoleInterface;

/**
 * Tests basic node_access functionality.
 *
 * Note that hook_node_access_records() is covered in another test class.
 *
 * @group node
 * @todo Cover hook_node_access in a separate test class.
 */
class NodeAccessTest extends NodeTestBase {
  protected function setUp() {
    parent::setUp();
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)->set('permissions', array())->save();
  }

  /**
   * Runs basic tests for node_access function.
   */
  function testNodeAccess() {
    // Ensures user without 'access content' permission can do nothing.
    $web_user1 = $this->drupalCreateUser(array('create page content', 'edit any page content', 'delete any page content'));
    $node1 = $this->drupalCreateNode(array('type' => 'page'));
    $this->assertNodeCreateAccess($node1->bundle(), FALSE, $web_user1);
    $this->assertNodeAccess(array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE), $node1, $web_user1);

    // Ensures user with 'bypass node access' permission can do everything.
    $web_user2 = $this->drupalCreateUser(array('bypass node access'));
    $node2 = $this->drupalCreateNode(array('type' => 'page'));
    $this->assertNodeCreateAccess($node2->bundle(), TRUE, $web_user2);
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $node2, $web_user2);

    // User cannot 'view own unpublished content'.
    $web_user3 = $this->drupalCreateUser(array('access content'));
    $node3 = $this->drupalCreateNode(array('status' => 0, 'uid' => $web_user3->id()));
    $this->assertNodeAccess(array('view' => FALSE), $node3, $web_user3);

    // User cannot create content without permission.
    $this->assertNodeCreateAccess($node3->bundle(), FALSE, $web_user3);

    // User can 'view own unpublished content', but another user cannot.
    $web_user4 = $this->drupalCreateUser(array('access content', 'view own unpublished content'));
    $web_user5 = $this->drupalCreateUser(array('access content', 'view own unpublished content'));
    $node4 = $this->drupalCreateNode(array('status' => 0, 'uid' => $web_user4->id()));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE), $node4, $web_user4);
    $this->assertNodeAccess(array('view' => FALSE), $node4, $web_user5);

    // Tests the default access provided for a published node.
    $node5 = $this->drupalCreateNode();
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $node5, $web_user3);

    // Tests the "edit any BUNDLE" and "delete any BUNDLE" permissions.
    $web_user6 = $this->drupalCreateUser(array('access content', 'edit any page content', 'delete any page content'));
    $node6 = $this->drupalCreateNode(array('type' => 'page'));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $node6, $web_user6);

    // Tests the "edit own BUNDLE" and "delete own BUNDLE" permission.
    $web_user7 = $this->drupalCreateUser(array('access content', 'edit own page content', 'delete own page content'));
    // User should not be able to edit or delete nodes they do not own.
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $node6, $web_user7);

    // User should be able to edit or delete nodes they own.
    $node7 = $this->drupalCreateNode(array('type' => 'page', 'uid' => $web_user7->id()));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $node7, $web_user7);
  }

}

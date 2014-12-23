<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAccessTest.
 */

namespace Drupal\node\Tests;

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
    $this->config('user.role.' . DRUPAL_AUTHENTICATED_RID)->set('permissions', array())->save();
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
  }

}

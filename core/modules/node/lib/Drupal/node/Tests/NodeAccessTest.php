<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeAccessTest.
 */

namespace Drupal\node\Tests;

/**
 * Test case to verify basic node_access functionality.
 * @todo Cover hook_node_access in a separate test class.
 * hook_node_access_records is covered in another test class.
 */
class NodeAccessTest extends NodeTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Node access',
      'description' => 'Test node_access function',
      'group' => 'Node',
    );
  }

  /**
   * Asserts node_access correctly grants or denies access.
   */
  function assertNodeAccess($ops, $node, $account) {
    foreach ($ops as $op => $result) {
      $msg = t("node_access returns @result with operation '@op'.", array('@result' => $result ? 'true' : 'false', '@op' => $op));
      $this->assertEqual($result, node_access($op, $node, $account), $msg);
    }
  }

  function setUp() {
    parent::setUp();
    // Clear permissions for authenticated users.
    db_delete('role_permission')
      ->condition('rid', DRUPAL_AUTHENTICATED_RID)
      ->execute();
  }

  /**
   * Runs basic tests for node_access function.
   */
  function testNodeAccess() {
    // Ensures user without 'access content' permission can do nothing.
    $web_user1 = $this->drupalCreateUser(array('create page content', 'edit any page content', 'delete any page content'));
    $node1 = $this->drupalCreateNode(array('type' => 'page'));
    $this->assertNodeAccess(array('create' => FALSE), 'page', $web_user1);
    $this->assertNodeAccess(array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE), $node1, $web_user1);

    // Ensures user with 'bypass node access' permission can do everything.
    $web_user2 = $this->drupalCreateUser(array('bypass node access'));
    $node2 = $this->drupalCreateNode(array('type' => 'page'));
    $this->assertNodeAccess(array('create' => TRUE), 'page', $web_user2);
    $this->assertNodeAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $node2, $web_user2);

    // User cannot 'view own unpublished content'.
    $web_user3 = $this->drupalCreateUser(array('access content'));
    $node3 = $this->drupalCreateNode(array('status' => 0, 'uid' => $web_user3->uid));
    $this->assertNodeAccess(array('view' => FALSE), $node3, $web_user3);

    // User cannot create content without permission.
    $this->assertNodeAccess(array('create' => FALSE), 'page', $web_user3);

    // User can 'view own unpublished content', but another user cannot.
    $web_user4 = $this->drupalCreateUser(array('access content', 'view own unpublished content'));
    $web_user5 = $this->drupalCreateUser(array('access content', 'view own unpublished content'));
    $node4 = $this->drupalCreateNode(array('status' => 0, 'uid' => $web_user4->uid));
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE), $node4, $web_user4);
    $this->assertNodeAccess(array('view' => FALSE), $node4, $web_user5);

    // Tests the default access provided for a published node.
    $node5 = $this->drupalCreateNode();
    $this->assertNodeAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $node5, $web_user3);
  }
}

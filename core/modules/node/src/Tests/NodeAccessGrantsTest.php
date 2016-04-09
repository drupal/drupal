<?php

namespace Drupal\node\Tests;

/**
 * Tests basic node_access functionality with hook_node_grants().
 *
 * This test just wraps the existing default permissions test while a module
 * that implements hook_node_grants() is enabled.
 *
 * @see \Drupal\node\NodeGrantDatabaseStorage
 *
 * @group node
 */
class NodeAccessGrantsTest extends NodeAccessTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_access_test_empty');

  /**
   * Test operations not supported by node grants.
   */
  function testUnsupportedOperation() {
    $web_user = $this->drupalCreateUser(['access content']);
    $node = $this->drupalCreateNode();
    $this->assertNodeAccess(['random_operation' => FALSE], $node, $web_user);
  }

}

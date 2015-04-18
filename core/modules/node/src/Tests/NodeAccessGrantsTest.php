<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeAccessGrantsTest.
 */

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

}

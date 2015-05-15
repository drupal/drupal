<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeLastChangedTest.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the node_last_changed() function.
 *
 * @group node
 */
class NodeLastChangedTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'node', 'field', 'system', 'text', 'filter');

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Runs basic tests for node_last_changed function.
   */
  function testNodeLastChanged() {
    $node = entity_create('node', array('type' => 'article', 'title' => $this->randomMachineName()));
    $node->save();

    // Test node last changed timestamp.
    $changed_timestamp = node_last_changed($node->id());
    $this->assertEqual($changed_timestamp, $node->getChangedTimeAcrossTranslations(), 'Expected last changed timestamp returned.');

    $changed_timestamp = node_last_changed($node->id(), $node->language()->getId());
    $this->assertEqual($changed_timestamp, $node->getChangedTime(), 'Expected last changed timestamp returned.');
  }
}

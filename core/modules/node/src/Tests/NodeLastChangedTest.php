<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeLastChangedTest.
 */

namespace Drupal\node\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the node_last_changed() function.
 */
class NodeLastChangedTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity', 'user', 'node', 'field', 'text', 'filter');

  public static function getInfo() {
    return array(
      'name' => 'Node Last Changed',
      'description' => 'Tests the node_last_changed() function.',
      'group' => 'Node',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installSchema('node', 'node');
    $this->installSchema('node', 'node_revision');
    $this->installSchema('node', 'node_field_data');
    $this->installSchema('node', 'node_field_revision');
    $this->installSchema('user', array('users'));
  }

  /**
   * Runs basic tests for node_last_changed function.
   */
  function testNodeLastChanged() {
    $node = entity_create('node', array('type' => 'article', 'title' => $this->randomName()));
    $node->save();

    // Test node last changed timestamp.
    $changed_timestamp = node_last_changed($node->id());
    $this->assertEqual($changed_timestamp, $node->getChangedTime(), 'Expected last changed timestamp returned.');

    $changed_timestamp = node_last_changed($node->id(), $node->language()->id);
    $this->assertEqual($changed_timestamp, $node->getChangedTime(), 'Expected last changed timestamp returned.');
  }
}

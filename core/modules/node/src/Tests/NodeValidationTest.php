<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeValidationTest.
 */

namespace Drupal\node\Tests;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests node validation constraints.
 */
class NodeValidationTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  public static function getInfo() {
    return array(
      'name' => 'Node Validation',
      'description' => 'Tests the node validation constraints.',
      'group' => 'Node',
    );
  }

  /**
   * Set the default field storage backend for fields created during tests.
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');

    // Create a node type for testing.
    $type = entity_create('node_type', array('type' => 'page', 'name' => 'page'));
    $type->save();
  }

  /**
   * Tests the node validation constraints.
   */
  public function testValidation() {
    $this->createUser();
    $node = entity_create('node', array('type' => 'page', 'title' => 'test', 'uid' => 1));
    $violations = $node->validate();
    $this->assertEqual(count($violations), 0, 'No violations when validating a default node.');

    $node->set('title', $this->randomString(256));
    $violations = $node->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when title is too long.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'title.0.value');
    $this->assertEqual($violations[0]->getMessage(), '<em class="placeholder">Title</em>: may not be longer than 255 characters.');

    $node->set('title', NULL);
    $violations = $node->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when title is not set.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'title');
    $this->assertEqual($violations[0]->getMessage(), 'This value should not be null.');

    // Make the title valid again.
    $node->set('title', $this->randomString());
    // Save the node so that it gets an ID and a changed date.
    $node->save();
    // Set the changed date to something in the far past.
    $node->set('changed', 433918800);
    $violations = $node->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when changed date is before the last changed date.');
    $this->assertEqual($violations[0]->getPropertyPath(), 'changed.0.value');
    $this->assertEqual($violations[0]->getMessage(), 'The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.');
  }
}

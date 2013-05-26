<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityBCDecoratorTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests Entity API base functionality.
 *
 * @todo: Remove once the EntityBCDecorator is removed.
 */
class EntityBCDecoratorTest extends EntityUnitTestBase  {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'text', 'node', 'comment');

  public static function getInfo() {
    return array(
      'name' => 'Entity Backward Compatibility Decorator',
      'description' => 'Tests the Entity Backward Compatibility Decorator',
      'group' => 'Entity API',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installSchema('user', array('users_roles', 'users_data', 'role_permission'));
    $this->installSchema('node', array('node', 'node_field_data', 'node_field_revision', 'node_type', 'node_access'));
    $this->installSchema('comment', array('comment', 'node_comment_statistics'));
  }

  /**
   * Tests using the entity BC decorator with entity properties.
   *
   * @see \Drupal\Core\Entity\EntityBCDecorator
   */
  public function testBCDecorator() {
    // Test using comment subject via the BC decorator.
    $this->createUser();
    $node = entity_create('node', array(
      'type' => 'page',
      'uid' => 1,
    ));
    $node->save();
    $comment = entity_create('comment', array(
      'nid' => $node->nid,
      'subject' => 'old-value',
    ));
    $comment->save();
    $bc_entity = $comment->getBCEntity();

    // Test reading of a defined property.
    $this->assertEqual($bc_entity->subject, 'old-value', 'Accessing entity property via BC decorator.');
    // Test writing of a defined property via the decorator.
    $bc_entity->subject = 'new';
    $this->assertEqual($bc_entity->subject, 'new', 'Updated defined entity property via BC decorator.');
    $this->assertEqual($comment->subject->value, 'new', 'Updated defined entity property  via BC decorator.');

    // Test writing of a defined property.
    $comment->subject->value = 'newer';
    $this->assertEqual($bc_entity->subject, 'newer', 'Updated defined entity property  via default entity class.');
    $this->assertEqual($comment->subject->value, 'newer', 'Updated defined entity property via default entity class.');

    // Test handling of an undefined property.
    $this->assertFalse(isset($bc_entity->foo), 'Checking if isset() on undefnied property.');
    $bc_entity->foo = 'bar';
    $this->assertEqual($bc_entity->foo, 'bar', 'Accessing undefined entity property via BC decorator.');
    $this->assertEqual($comment->foo, 'bar', 'Accessing undefined entity property via default entity class.');
    $this->assertTrue(isset($bc_entity->foo), 'Checking if isset() on undefnied property.');
  }
}

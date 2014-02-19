<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentNodeChangesTest.
 */

namespace Drupal\comment\Tests;

/**
 * Tests that comments behave correctly when the node is changed.
 */
class CommentNodeChangesTest extends CommentTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Comment deletion on node changes',
      'description' => 'Tests that comments behave correctly when the node is changed.',
      'group' => 'Comment',
    );
  }

  /**
   * Tests that comments are deleted with the node.
   */
  function testNodeDeletion() {
    $this->drupalLogin($this->web_user);
    $comment = $this->postComment($this->node, $this->randomName(), $this->randomName());
    $this->assertTrue($comment->id(), 'The comment could be loaded.');
    $this->node->delete();
    $this->assertFalse(comment_load($comment->id()), 'The comment could not be loaded after the node was deleted.');
    // Make sure the comment field and all its instances are deleted when node
    // type is deleted.
    $this->assertNotNull(entity_load('field_config', 'node.comment'), 'Comment field exists');
    $this->assertNotNull(entity_load('field_instance_config', 'node.article.comment'), 'Comment instance exists');
    // Delete the node type.
    entity_delete_multiple('node_type', array($this->node->bundle()));
    $this->assertNull(entity_load('field_config', 'node.comment'), 'Comment field deleted');
    $this->assertNull(entity_load('field_instance_config', 'node.article.comment'), 'Comment instance deleted');
  }
}

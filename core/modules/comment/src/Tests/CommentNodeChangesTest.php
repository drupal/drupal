<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentNodeChangesTest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Entity\Comment;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests that comments behave correctly when the node is changed.
 *
 * @group comment
 */
class CommentNodeChangesTest extends CommentTestBase {

  /**
   * Tests that comments are deleted with the node.
   */
  function testNodeDeletion() {
    $this->drupalLogin($this->webUser);
    $comment = $this->postComment($this->node, $this->randomMachineName(), $this->randomMachineName());
    $this->assertTrue($comment->id(), 'The comment could be loaded.');
    $this->node->delete();
    $this->assertFalse(Comment::load($comment->id()), 'The comment could not be loaded after the node was deleted.');
    // Make sure the comment field storage and all its fields are deleted when
    // the node type is deleted.
    $this->assertNotNull(entity_load('field_storage_config', 'node.comment'), 'Comment field storage exists');
    $this->assertNotNull(FieldConfig::load('node.article.comment'), 'Comment field exists');
    // Delete the node type.
    entity_delete_multiple('node_type', array($this->node->bundle()));
    $this->assertNull(entity_load('field_storage_config', 'node.comment'), 'Comment field storage deleted');
    $this->assertNull(FieldConfig::load('node.article.comment'), 'Comment field deleted');
  }
}

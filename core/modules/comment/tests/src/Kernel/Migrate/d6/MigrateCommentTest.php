<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\node\NodeInterface;

/**
 * Tests the migration of comments from Drupal 6.
 *
 * @group comment
 * @group migrate_drupal_6
 */
class MigrateCommentTest extends MigrateDrupal6TestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'content_translation',
    'language',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['comment']);

    $this->migrateContent();
    $this->executeMigrations([
      'language',
      'd6_language_content_settings',
      'd6_node',
      'd6_node_translation',
      'd6_comment_type',
      'd6_comment_field',
      'd6_comment_field_instance',
      'd6_comment_entity_display',
      'd6_comment_entity_form_display',
      'd6_comment',
    ]);
  }

  /**
   * Tests the migrated comments.
   */
  public function testMigration(): void {
    $comment = Comment::load(1);
    $this->assertSame('The first comment.', $comment->getSubject());
    $this->assertSame('The first comment body.', $comment->comment_body->value);
    $this->assertSame('filtered_html', $comment->comment_body->format);
    $this->assertNull($comment->pid->target_id);
    $this->assertSame('1', $comment->getCommentedEntityId());
    $this->assertSame('node', $comment->getCommentedEntityTypeId());
    $this->assertSame('en', $comment->language()->getId());
    $this->assertSame('comment_node_story', $comment->getTypeId());
    $this->assertSame('203.0.113.1', $comment->getHostname());

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('1', $node->id());

    $comment = Comment::load(2);
    $this->assertSame('The response to the second comment.', $comment->subject->value);
    $this->assertSame('3', $comment->pid->target_id);
    $this->assertSame('203.0.113.2', $comment->getHostname());

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('1', $node->id());

    $comment = Comment::load(3);
    $this->assertSame('The second comment.', $comment->subject->value);
    $this->assertNull($comment->pid->target_id);
    $this->assertSame('203.0.113.3', $comment->getHostname());

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('1', $node->id());

    // Tests that the language of the comment is migrated from the node.
    $comment = Comment::load(7);
    $this->assertSame('Comment to John Smith - EN', $comment->subject->value);
    $this->assertSame('This is an English comment.', $comment->comment_body->value);
    $this->assertSame('21', $comment->getCommentedEntityId());
    $this->assertSame('node', $comment->getCommentedEntityTypeId());
    $this->assertSame('en', $comment->language()->getId());

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('21', $node->id());

    // Tests that the comment language is correct and that the commented entity
    // is correctly migrated when the comment was posted to a node translation.
    $comment = Comment::load(8);
    $this->assertSame('Comment to John Smith - FR', $comment->subject->value);
    $this->assertSame('This is a French comment.', $comment->comment_body->value);
    $this->assertSame('21', $comment->getCommentedEntityId());
    $this->assertSame('node', $comment->getCommentedEntityTypeId());
    $this->assertSame('fr', $comment->language()->getId());

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('21', $node->id());
  }

}

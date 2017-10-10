<?php

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
  public static $modules = ['comment', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);

    // The entity.node.canonical route must exist when the RDF hook is called.
    $this->container->get('router.builder')->rebuild();

    $this->migrateContent();
    $this->executeMigrations([
      'd6_node',
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
  public function testMigration() {
    $comment = Comment::load(1);
    $this->assertSame('The first comment.', $comment->getSubject());
    $this->assertSame('The first comment body.', $comment->comment_body->value);
    $this->assertSame('filtered_html', $comment->comment_body->format);
    $this->assertSame(NULL, $comment->pid->target_id);
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
    $this->assertSame(NULL, $comment->pid->target_id);
    $this->assertSame('203.0.113.3', $comment->getHostname());

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('1', $node->id());
  }

}

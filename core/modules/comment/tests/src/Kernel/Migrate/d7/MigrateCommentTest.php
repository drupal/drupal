<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\node\NodeInterface;

/**
 * Tests migration of comments from Drupal 7.
 *
 * @group comment
 */
class MigrateCommentTest extends MigrateDrupal7TestBase {

  public static $modules = ['filter', 'node', 'comment', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');

    $this->executeMigrations([
      'd7_filter_format',
      'd7_user_role',
      'd7_user',
    ]);
    $this->executeMigration('d7_node_type');
    // We only need the test_content_type node migration to run for real, so
    // mock all the others.
    $this->prepareMigrations(array(
      'd7_node' => array(
        array(array(0), array(0)),
      ),
    ));
    $this->executeMigrations([
      'd7_node:test_content_type',
      'd7_comment_type',
      'd7_comment',
    ]);
  }

  /**
   * Tests migration of comments from Drupal 7.
   */
  public function testCommentMigration() {
    $comment = Comment::load(1);
    $this->assertTrue($comment instanceof CommentInterface);
    /** @var \Drupal\comment\CommentInterface $comment */
    $this->assertIdentical('A comment', $comment->getSubject());
    $this->assertIdentical('1421727536', $comment->getCreatedTime());
    $this->assertIdentical('1421727536', $comment->getChangedTime());
    $this->assertTrue($comment->getStatus());
    $this->assertIdentical('admin', $comment->getAuthorName());
    $this->assertIdentical('admin@local.host', $comment->getAuthorEmail());
    $this->assertIdentical('This is a comment', $comment->comment_body->value);
    $this->assertIdentical('filtered_html', $comment->comment_body->format);

    $node = $comment->getCommentedEntity();
    $this->assertTrue($node instanceof NodeInterface);
    $this->assertIdentical('1', $node->id());
  }

}

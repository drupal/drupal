<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Migrate\d7\MigrateCommentTest.
 */

namespace Drupal\comment\Tests\Migrate\d7;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

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

    $this->executeMigration('d7_filter_format');
    $this->executeMigration('d7_user_role');
    $this->executeMigration('d7_user');
    // The test database doesn't include uid 1, so we'll need to create it.
    User::create(array(
      'uid' => 1,
      'name' => 'admin',
      'mail' => 'admin@local.host',
    ))->save();
    $this->executeMigration('d7_node_type');
    // We only need the test_content_type node migration to run for real, so
    // mock all the others.
    $this->prepareMigrations(array(
      'd7_node:*' => array(
        array(array(0), array(0)),
      ),
    ));
    $this->executeMigration('d7_node__test_content_type');
    $this->executeMigration('d7_comment_type');
    $this->executeMigration('d7_comment');
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

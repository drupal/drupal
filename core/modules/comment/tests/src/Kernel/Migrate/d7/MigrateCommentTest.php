<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\comment\Entity\Comment;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\node\NodeInterface;

/**
 * Tests the migration of comments from Drupal 7.
 *
 * @group comment
 * @group migrate_drupal_7
 */
class MigrateCommentTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'content_translation',
    'datetime',
    'filter',
    'image',
    'language',
    'link',
    'menu_ui',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installConfig(['comment', 'node']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);
    $this->executeMigrations([
      'language',
      'd7_node_type',
      'd7_language_content_settings',
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_node',
      'd7_node_translation',
      'd7_comment_type',
      'd7_comment_field',
      'd7_comment_field_instance',
      'd7_comment_entity_display',
      'd7_comment_entity_form_display',
      'd7_taxonomy_vocabulary',
      'd7_field',
      'd7_field_instance',
      'd7_comment',
    ]);
  }

  /**
   * Tests the migrated comments.
   */
  public function testMigration() {
    $comment = Comment::load(1);
    $this->assertInstanceOf(Comment::class, $comment);
    $this->assertSame('A comment', $comment->getSubject());
    $this->assertSame('1421727536', $comment->getCreatedTime());
    $this->assertSame('1421727536', $comment->getChangedTime());
    $this->assertTrue($comment->getStatus());
    $this->assertSame('admin', $comment->getAuthorName());
    $this->assertSame('admin@local.host', $comment->getAuthorEmail());
    $this->assertSame('This is a comment', $comment->comment_body->value);
    $this->assertSame('filtered_html', $comment->comment_body->format);
    $this->assertSame('2001:db8:ffff:ffff:ffff:ffff:ffff:ffff', $comment->getHostname());
    $this->assertSame('und', $comment->language()->getId());
    $this->assertSame('1000000', $comment->field_integer->value);

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('1', $node->id());

    // Tests that comments that used the Drupal 7 Title module and that have
    // their subject replaced by a real field are correctly migrated.
    $comment = Comment::load(2);
    $this->assertInstanceOf(Comment::class, $comment);
    $this->assertSame('TNG for the win!', $comment->getSubject());
    $this->assertSame('TNG is better than DS9.', $comment->comment_body->value);
    $this->assertSame('en', $comment->language()->getId());

    // Tests that the commented entity is correctly migrated when the comment
    // was posted to a node translation.
    $comment = Comment::load(3);
    $this->assertInstanceOf(Comment::class, $comment);
    $this->assertSame('Comment to IS translation', $comment->getSubject());
    $this->assertSame('This is a comment to an Icelandic translation.', $comment->comment_body->value);
    $this->assertSame('2', $comment->getCommentedEntityId());
    $this->assertSame('node', $comment->getCommentedEntityTypeId());
    $this->assertSame('is', $comment->language()->getId());

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('2', $node->id());
  }

}

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
  protected static $modules = [
    'comment',
    'content_translation',
    'datetime',
    'datetime_range',
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
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);
    $this->migrateContent();
    $this->executeMigrations([
      'language',
      'd7_node_type',
      'd7_language_content_settings',
      'd7_node_translation',
      'd7_comment_field',
      'd7_comment_field_instance',
      'd7_comment_entity_display',
      'd7_comment_entity_form_display',
      'd7_taxonomy_vocabulary',
      'd7_field',
      'd7_field_instance',
      'd7_comment',
      'd7_entity_translation_settings',
      'd7_comment_entity_translation',
    ]);
  }

  /**
   * Tests the migrated comments.
   */
  public function testMigration() {
    $comment = Comment::load(1);
    $this->assertInstanceOf(Comment::class, $comment);
    $this->assertSame('Subject field in English', $comment->getSubject());
    $this->assertSame('1421727536', $comment->getCreatedTime());
    $this->assertSame(1421727536, $comment->getChangedTime());
    $this->assertTrue($comment->isPublished());
    $this->assertSame('admin', $comment->getAuthorName());
    $this->assertSame('admin@local.host', $comment->getAuthorEmail());
    $this->assertSame('This is a comment', $comment->comment_body->value);
    $this->assertSame('filtered_html', $comment->comment_body->format);
    $this->assertSame('2001:db8:ffff:ffff:ffff:ffff:ffff:ffff', $comment->getHostname());
    $this->assertSame('en', $comment->language()->getId());
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

    // Tests a comment migrated from Drupal 6 to Drupal 7 that did not have a
    // language.
    $comment = Comment::load(4);
    $this->assertInstanceOf(Comment::class, $comment);
    $this->assertSame('Comment without language', $comment->getSubject());
    $this->assertSame('1426781880', $comment->getCreatedTime());
    $this->assertSame(1426781880, $comment->getChangedTime());
    $this->assertTrue($comment->isPublished());
    $this->assertSame('Bob', $comment->getAuthorName());
    $this->assertSame('bob@local.host', $comment->getAuthorEmail());
    $this->assertSame('A comment without language (migrated from Drupal 6)', $comment->comment_body->value);
    $this->assertSame('filtered_html', $comment->comment_body->format);
    $this->assertSame('drupal7.local', $comment->getHostname());
    $this->assertSame('und', $comment->language()->getId());
    $this->assertSame('10', $comment->field_integer->value);

    $node = $comment->getCommentedEntity();
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertSame('1', $node->id());

    // Tests the migration of comment entity translations.
    $manager = $this->container->get('content_translation.manager');

    // Get the comment and its translations.
    $comment = Comment::load(1);
    $comment_fr = $comment->getTranslation('fr');
    $comment_is = $comment->getTranslation('is');

    // Test that fields translated with Entity Translation are migrated.
    $this->assertSame('Subject field in English', $comment->getSubject());
    $this->assertSame('Subject field in French', $comment_fr->getSubject());
    $this->assertSame('Subject field in Icelandic', $comment_is->getSubject());
    $this->assertSame('1000000', $comment->field_integer->value);
    $this->assertSame('2000000', $comment_fr->field_integer->value);
    $this->assertSame('3000000', $comment_is->field_integer->value);

    // Test that the French translation metadata is correctly migrated.
    $metadata_fr = $manager->getTranslationMetadata($comment_fr);
    $this->assertFalse($metadata_fr->isPublished());
    $this->assertSame('en', $metadata_fr->getSource());
    $this->assertSame('1', $metadata_fr->getAuthor()->uid->value);
    $this->assertSame('1531837764', $metadata_fr->getCreatedTime());
    $this->assertSame(1531837764, $metadata_fr->getChangedTime());
    $this->assertFalse($metadata_fr->isOutdated());

    // Test that the Icelandic translation metadata is correctly migrated.
    $metadata_is = $manager->getTranslationMetadata($comment_is);
    $this->assertTrue($metadata_is->isPublished());
    $this->assertSame('en', $metadata_is->getSource());
    $this->assertSame('2', $metadata_is->getAuthor()->uid->value);
    $this->assertSame('1531838064', $metadata_is->getCreatedTime());
    $this->assertSame(1531838064, $metadata_is->getChangedTime());
    $this->assertTrue($metadata_is->isOutdated());
  }

}

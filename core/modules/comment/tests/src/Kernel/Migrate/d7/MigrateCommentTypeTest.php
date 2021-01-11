<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\comment\Entity\CommentType;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of comment types from Drupal 7.
 *
 * @group comment
 * @group migrate_drupal_7
 */
class MigrateCommentTypeTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'comment', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateCommentTypes();
  }

  /**
   * Asserts a comment type entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $label
   *   The entity label.
   */
  protected function assertEntity($id, $label) {
    $entity = CommentType::load($id);
    $this->assertInstanceOf(CommentType::class, $entity);
    $this->assertSame($label, $entity->label());
    $this->assertSame('node', $entity->getTargetEntityTypeId());
  }

  /**
   * Tests the migrated comment types.
   */
  public function testMigration() {
    $comment_fields = [
      'comment' => 'Default comment setting',
      'comment_default_mode' => 'Default display mode',
      'comment_default_per_page' => 'Default comments per page',
      'comment_anonymous' => 'Anonymous commenting',
      'comment_subject_field' => 'Comment subject field',
      'comment_preview' => 'Preview comment',
      'comment_form_location' => 'Location of comment submission form',
    ];
    foreach ($comment_fields as $field => $description) {
      $this->assertEquals($description, $this->migration->getSourcePlugin()->fields()[$field]);
    }

    $this->assertEntity('comment_node_article', 'Article comment');
    $this->assertEntity('comment_node_blog', 'Blog entry comment');
    $this->assertEntity('comment_node_book', 'Book page comment');
    $this->assertEntity('comment_forum', 'Forum topic comment');
    $this->assertEntity('comment_node_page', 'Basic page comment');
    $this->assertEntity('comment_node_test_content_type', 'Test content type comment');
    $this->assertEntity('comment_node_a_thirty_two_char', 'Test long name comment');
  }

}

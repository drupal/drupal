<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of comment fields from Drupal 7.
 *
 * @group comment
 * @group migrate_drupal_7
 */
class MigrateCommentFieldTest extends MigrateDrupal7TestBase {

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
    $this->executeMigration('d7_comment_field');
  }

  /**
   * Asserts a comment field entity.
   *
   * @param string $comment_type
   *   The comment type.
   */
  protected function assertEntity($comment_type) {
    $entity = FieldStorageConfig::load('node.' . $comment_type);
    $this->assertInstanceOf(FieldStorageConfig::class, $entity);
    $this->assertSame('node', $entity->getTargetEntityTypeId());
    $this->assertSame('comment', $entity->getType());
    $this->assertSame($comment_type, $entity->getSetting('comment_type'));
  }

  /**
   * Tests the migrated comment fields.
   */
  public function testMigration() {
    $this->assertEntity('comment_node_page');
    $this->assertEntity('comment_node_article');
    $this->assertEntity('comment_node_blog');
    $this->assertEntity('comment_node_book');
    $this->assertEntity('comment_forum');
    $this->assertEntity('comment_node_test_content_type');
  }

}

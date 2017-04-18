<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\comment\CommentTypeInterface;
use Drupal\comment\Entity\CommentType;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of comment types from Drupal 7.
 *
 * @group comment
 */
class MigrateCommentTypeTest extends MigrateDrupal7TestBase {

  public static $modules = ['node', 'comment', 'text', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
    ]);
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
    $this->assertTrue($entity instanceof CommentTypeInterface);
    /** @var \Drupal\comment\CommentTypeInterface $entity */
    $this->assertIdentical($label, $entity->label());
    $this->assertIdentical('node', $entity->getTargetEntityTypeId());
  }

  /**
   * Tests the migrated comment types.
   */
  public function testMigration() {
    $this->assertEntity('comment_node_page', 'Basic page comment');
    $this->assertEntity('comment_node_article', 'Article comment');
    $this->assertEntity('comment_node_blog', 'Blog entry comment');
    $this->assertEntity('comment_node_book', 'Book page comment');
    $this->assertEntity('comment_node_forum', 'Forum topic comment');
    $this->assertEntity('comment_node_test_content_type', 'Test content type comment');

    $migration = $this->getMigration('d7_comment_type');
    // Validate that the source count and processed count match up.
    $this->assertIdentical($migration->getSourcePlugin()->count(), $migration->getIdMap()->processedCount());
  }

}

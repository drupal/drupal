<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests creation of comment reference fields for each comment type defined
 * in Drupal 7.
 *
 * @group comment
 */
class MigrateCommentFieldTest extends MigrateDrupal7TestBase {

  public static $modules = ['node', 'comment', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
      'd7_comment_field',
    ]);
  }

  /**
   * Asserts a comment field entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $comment_type
   *   The comment type (bundle ID) the field references.
   */
  protected function assertEntity($id, $comment_type) {
    $entity = FieldStorageConfig::load($id);
    $this->assertTrue($entity instanceof FieldStorageConfigInterface);
    /** @var \Drupal\field\FieldStorageConfigInterface $entity */
    $this->assertIdentical('node', $entity->getTargetEntityTypeId());
    $this->assertIdentical('comment', $entity->getType());
    $this->assertIdentical($comment_type, $entity->getSetting('comment_type'));
  }

  /**
   * Tests the migrated fields.
   */
  public function testMigration() {
    $this->assertEntity('node.comment_node_page', 'comment_node_page');
    $this->assertEntity('node.comment_node_article', 'comment_node_article');
    $this->assertEntity('node.comment_node_blog', 'comment_node_blog');
    $this->assertEntity('node.comment_node_book', 'comment_node_book');
    $this->assertEntity('node.comment_node_forum', 'comment_node_forum');
    $this->assertEntity('node.comment_node_test_content_type', 'comment_node_test_content_type');
  }

}

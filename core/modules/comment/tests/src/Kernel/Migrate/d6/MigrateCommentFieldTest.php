<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the migration of comment fields from Drupal 6.
 *
 * @group comment
 * @group migrate_drupal_6
 */
class MigrateCommentFieldTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['comment']);
    $this->executeMigrations([
      'd6_comment_type',
      'd6_comment_field',
    ]);
  }

  /**
   * Asserts a comment field entity.
   *
   * @param string $comment_type
   *   The comment type.
   *
   * @internal
   */
  protected function assertEntity(string $comment_type): void {
    $entity = FieldStorageConfig::load('node.' . $comment_type);
    $this->assertInstanceOf(FieldStorageConfig::class, $entity);
    $this->assertSame('node', $entity->getTargetEntityTypeId());
    $this->assertSame('comment', $entity->getType());
    $this->assertSame($comment_type, $entity->getSetting('comment_type'));
  }

  /**
   * Tests the migrated comment fields.
   */
  public function testMigration(): void {
    $this->assertEntity('comment_node_article');
    $this->assertEntity('comment_node_company');
    $this->assertEntity('comment_node_employee');
    $this->assertEntity('comment_node_event');
    $this->assertEntity('comment_forum');
    $this->assertEntity('comment_node_page');
    $this->assertEntity('comment_node_sponsor');
    $this->assertEntity('comment_node_story');
    $this->assertEntity('comment_node_test_event');
    $this->assertEntity('comment_node_test_page');
    $this->assertEntity('comment_node_test_planet');
    $this->assertEntity('comment_node_test_story');
  }

}

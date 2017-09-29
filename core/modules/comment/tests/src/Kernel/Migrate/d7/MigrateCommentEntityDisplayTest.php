<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of comment display configuration.
 *
 * @group comment
 */
class MigrateCommentEntityDisplayTest extends MigrateDrupal7TestBase {

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
      'd7_comment_field',
      'd7_comment_field_instance',
      'd7_comment_entity_display',
    ]);
  }

  /**
   * Asserts a display entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $component_id
   *   The ID of the display component.
   */
  protected function assertDisplay($id, $component_id) {
    $component = EntityViewDisplay::load($id)->getComponent($component_id);
    $this->assertTrue(is_array($component));
    $this->assertIdentical('hidden', $component['label']);
    $this->assertIdentical('comment_default', $component['type']);
    $this->assertIdentical(20, $component['weight']);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration() {
    $this->assertDisplay('node.page.default', 'comment_node_page');
    $this->assertDisplay('node.article.default', 'comment_node_article');
    $this->assertDisplay('node.book.default', 'comment_node_book');
    $this->assertDisplay('node.blog.default', 'comment_node_blog');
    $this->assertDisplay('node.forum.default', 'comment_forum');
    $this->assertDisplay('node.test_content_type.default', 'comment_node_test_content_type');
  }

}

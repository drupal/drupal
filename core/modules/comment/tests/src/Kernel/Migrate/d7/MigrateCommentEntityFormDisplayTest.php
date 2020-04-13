<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of comment form display from Drupal 7.
 *
 * @group comment
 * @group migrate_drupal_7
 */
class MigrateCommentEntityFormDisplayTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'comment', 'text', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateContentTypes();
    $this->migrateCommentTypes();
    $this->executeMigrations([
      'd7_comment_field',
      'd7_comment_field_instance',
      'd7_comment_entity_form_display',
    ]);
  }

  /**
   * Asserts various aspects of a comment component in an entity form display.
   *
   * @param string $id
   *   The entity ID.
   * @param string $component_id
   *   The ID of the form component.
   */
  protected function assertDisplay($id, $component_id) {
    $component = EntityFormDisplay::load($id)->getComponent($component_id);
    $this->assertIsArray($component);
    $this->assertSame('comment_default', $component['type']);
    $this->assertSame(20, $component['weight']);
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

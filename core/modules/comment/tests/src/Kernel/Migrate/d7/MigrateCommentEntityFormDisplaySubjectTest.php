<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of comment form's subject display configuration.
 *
 * @group comment
 */
class MigrateCommentEntityFormDisplaySubjectTest extends MigrateDrupal7TestBase {

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
      'd7_comment_entity_form_display_subject',
    ]);
  }

  /**
   * Asserts a display entity.
   *
   * @param string $id
   *   The entity ID.
   */
  protected function assertDisplay($id) {
    $component = EntityFormDisplay::load($id)->getComponent('subject');
    $this->assertTrue(is_array($component));
    $this->assertIdentical('string_textfield', $component['type']);
    $this->assertIdentical(10, $component['weight']);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration() {
    $this->assertDisplay('comment.comment_node_page.default');
    $this->assertDisplay('comment.comment_node_article.default');
    $this->assertDisplay('comment.comment_node_book.default');
    $this->assertDisplay('comment.comment_node_blog.default');
    $this->assertDisplay('comment.comment_node_forum.default');
    $this->assertDisplay('comment.comment_node_test_content_type.default');
  }

}

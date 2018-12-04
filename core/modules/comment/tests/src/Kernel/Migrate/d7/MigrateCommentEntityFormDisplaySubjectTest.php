<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d7;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the migration of comment form's subject display from Drupal 7.
 *
 * @group comment
 * @group migrate_drupal_7
 */
class MigrateCommentEntityFormDisplaySubjectTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'comment', 'text', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateCommentTypes();
    $this->executeMigration('d7_comment_entity_form_display_subject');
  }

  /**
   * Asserts that the comment subject field is visible for a node type.
   *
   * @param string $id
   *   The entity form display ID.
   */
  protected function assertSubjectVisible($id) {
    $component = EntityFormDisplay::load($id)->getComponent('subject');
    $this->assertInternalType('array', $component);
    $this->assertSame('string_textfield', $component['type']);
    $this->assertSame(10, $component['weight']);
  }

  /**
   * Asserts that the comment subject field is not visible for a node type.
   *
   * @param string $id
   *   The entity form display ID.
   */
  protected function assertSubjectNotVisible($id) {
    $component = EntityFormDisplay::load($id)->getComponent('subject');
    $this->assertNull($component);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration() {
    $this->assertSubjectVisible('comment.comment_node_page.default');
    $this->assertSubjectVisible('comment.comment_node_article.default');
    $this->assertSubjectVisible('comment.comment_node_book.default');
    $this->assertSubjectVisible('comment.comment_node_blog.default');
    $this->assertSubjectVisible('comment.comment_forum.default');
    $this->assertSubjectNotVisible('comment.comment_node_test_content_type.default');
  }

}

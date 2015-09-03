<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Migrate\d7\MigrateCommentEntityFormDisplayTest.
 */

namespace Drupal\comment\Tests\Migrate\d7;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of comment form display configuration.
 *
 * @group comment
 */
class MigrateCommentEntityFormDisplayTest extends MigrateDrupal7TestBase {

  public static $modules = ['node', 'comment', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_node_type');
    $this->executeMigration('d7_comment_type');
    $this->executeMigration('d7_comment_field');
    $this->executeMigration('d7_comment_field_instance');
    $this->executeMigration('d7_comment_entity_form_display');
  }

  /**
   * Asserts a display entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $component
   *   The ID of the form component.
   */
  protected function assertDisplay($id, $component_id) {
    $component = EntityFormDisplay::load($id)->getComponent($component_id);
    $this->assertTrue(is_array($component));
    $this->assertIdentical('comment_default', $component['type']);
    $this->assertIdentical(20, $component['weight']);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration() {
    $this->assertDisplay('node.page.default', 'comment');
    $this->assertDisplay('node.article.default', 'comment');
    $this->assertDisplay('node.book.default', 'comment');
    $this->assertDisplay('node.blog.default', 'comment');
    $this->assertDisplay('node.forum.default', 'comment');
    $this->assertDisplay('node.test_content_type.default', 'comment');
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the migration of comment form's subject display from Drupal 6.
 *
 * @group comment
 * @group migrate_drupal_6
 */
class MigrateCommentEntityFormDisplaySubjectTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['comment']);
    $this->executeMigrations([
      'd6_comment_type',
      'd6_comment_entity_form_display_subject',
    ]);
  }

  /**
   * Asserts that the comment subject field is visible for a node type.
   *
   * @param string $id
   *   The entity form display ID.
   *
   * @internal
   */
  protected function assertSubjectVisible(string $id): void {
    $component = EntityFormDisplay::load($id)->getComponent('subject');
    $this->assertIsArray($component);
    $this->assertSame('string_textfield', $component['type']);
    $this->assertSame(10, $component['weight']);
  }

  /**
   * Asserts that the comment subject field is not visible for a node type.
   *
   * @param string $id
   *   The entity form display ID.
   *
   * @internal
   */
  protected function assertSubjectNotVisible(string $id): void {
    $component = EntityFormDisplay::load($id)->getComponent('subject');
    $this->assertNull($component);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration(): void {
    $this->assertSubjectVisible('comment.comment_node_article.default');
    $this->assertSubjectVisible('comment.comment_node_company.default');
    $this->assertSubjectVisible('comment.comment_node_employee.default');
    $this->assertSubjectVisible('comment.comment_node_page.default');
    $this->assertSubjectVisible('comment.comment_node_sponsor.default');
    $this->assertSubjectNotVisible('comment.comment_node_story.default');
    $this->assertSubjectVisible('comment.comment_node_test_event.default');
    $this->assertSubjectVisible('comment.comment_node_test_page.default');
    $this->assertSubjectVisible('comment.comment_node_test_planet.default');
    $this->assertSubjectVisible('comment.comment_node_test_story.default');
  }

}

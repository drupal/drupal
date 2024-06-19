<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the migration of comment form display from Drupal 6.
 *
 * @group comment
 * @group migrate_drupal_6
 */
class MigrateCommentEntityFormDisplayTest extends MigrateDrupal6TestBase {

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
    $this->migrateContentTypes();
    $this->executeMigrations([
      'd6_comment_type',
      'd6_comment_field',
      'd6_comment_field_instance',
      'd6_comment_entity_form_display',
    ]);
  }

  /**
   * Asserts various aspects of a comment component in an entity form display.
   *
   * @param string $id
   *   The entity ID.
   * @param string $component_id
   *   The ID of the form component.
   *
   * @internal
   */
  protected function assertDisplay(string $id, string $component_id): void {
    $component = EntityFormDisplay::load($id)->getComponent($component_id);
    $this->assertIsArray($component);
    $this->assertSame('comment_default', $component['type']);
    $this->assertSame(20, $component['weight']);
  }

  /**
   * Tests the migrated display configuration.
   */
  public function testMigration(): void {
    $this->assertDisplay('node.article.default', 'comment_node_article');
    $this->assertDisplay('node.company.default', 'comment_node_company');
    $this->assertDisplay('node.employee.default', 'comment_node_employee');
    $this->assertDisplay('node.event.default', 'comment_node_event');
    $this->assertDisplay('node.forum.default', 'comment_forum');
    $this->assertDisplay('node.page.default', 'comment_node_page');
    $this->assertDisplay('node.sponsor.default', 'comment_node_sponsor');
    $this->assertDisplay('node.story.default', 'comment_node_story');
    $this->assertDisplay('node.test_event.default', 'comment_node_test_event');
    $this->assertDisplay('node.test_page.default', 'comment_node_test_page');
    $this->assertDisplay('node.test_planet.default', 'comment_node_test_planet');
    $this->assertDisplay('node.test_story.default', 'comment_node_test_story');
  }

}

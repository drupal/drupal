<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upload form entity display.
 *
 * @group migrate_drupal_6
 */
class MigrateUploadEntityFormDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateFields();
  }

  /**
   * Tests Drupal 6 upload settings to Drupal 8 entity form display migration.
   */
  public function testUploadEntityFormDisplay(): void {
    $this->executeMigration('d6_upload_entity_form_display');

    $display = EntityFormDisplay::load('node.page.default');
    $component = $display->getComponent('upload');
    $this->assertSame('file_generic', $component['type']);

    $display = EntityFormDisplay::load('node.story.default');
    $component = $display->getComponent('upload');
    $this->assertSame('file_generic', $component['type']);

    // Assure this doesn't exist.
    $display = EntityFormDisplay::load('node.article.default');
    $component = $display->getComponent('upload');
    $this->assertNull($component);

    $this->assertSame([['node', 'page', 'default', 'upload']], $this->getMigration('d6_upload_entity_form_display')->getIdMap()->lookupDestinationIds(['page']));
  }

  /**
   * Tests that entity displays are ignored appropriately.
   *
   * Entity displays should be ignored when they belong to node types which
   * were not migrated.
   */
  public function testSkipNonExistentNodeType(): void {
    // The "story" node type is migrated by d6_node_type but we need to pretend
    // that it didn't occur, so record that in the map table.
    $this->mockFailure('d6_node_type', ['type' => 'story']);

    // d6_upload_entity_form_display should skip over the "story" node type
    // config because according to the map table, it didn't occur.
    $migration = $this->getMigration('d6_upload_entity_form_display');

    $this->executeMigration($migration);
    $this->assertNull($migration->getIdMap()->lookupDestinationIds(['node_type' => 'story'])[0][0]);
  }

}

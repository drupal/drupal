<?php

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
  public static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
    $this->executeMigration('d6_upload_entity_form_display');
  }

  /**
   * Tests the Drupal 6 upload settings to Drupal 8 entity form display migration.
   */
  public function testUploadEntityFormDisplay() {
    $display = EntityFormDisplay::load('node.page.default');
    $component = $display->getComponent('upload');
    $this->assertIdentical('file_generic', $component['type']);

    $display = EntityFormDisplay::load('node.story.default');
    $component = $display->getComponent('upload');
    $this->assertIdentical('file_generic', $component['type']);

    // Assure this doesn't exist.
    $display = EntityFormDisplay::load('node.article.default');
    $component = $display->getComponent('upload');
    $this->assertTrue(is_null($component));

    $this->assertIdentical(['node', 'page', 'default', 'upload'], $this->getMigration('d6_upload_entity_form_display')->getIdMap()->lookupDestinationID(['page']));
  }

}

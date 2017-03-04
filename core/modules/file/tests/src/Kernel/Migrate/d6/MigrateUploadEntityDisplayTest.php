<?php

namespace Drupal\Tests\file\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upload entity display.
 *
 * @group migrate_drupal_6
 */
class MigrateUploadEntityDisplayTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
    $this->executeMigration('d6_upload_entity_display');
  }

  /**
   * Tests the Drupal 6 upload settings to Drupal 8 entity display migration.
   */
  public function testUploadEntityDisplay() {
    $display = EntityViewDisplay::load('node.page.default');
    $component = $display->getComponent('upload');
    $this->assertIdentical('file_default', $component['type']);

    $display = EntityViewDisplay::load('node.story.default');
    $component = $display->getComponent('upload');
    $this->assertIdentical('file_default', $component['type']);

    // Assure this doesn't exist.
    $display = EntityViewDisplay::load('node.article.default');
    $component = $display->getComponent('upload');
    $this->assertTrue(is_null($component));

    $this->assertIdentical(['node', 'page', 'default', 'upload'], $this->getMigration('d6_upload_entity_display')->getIdMap()->lookupDestinationID(['page']));
  }

}

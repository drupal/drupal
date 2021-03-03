<?php

namespace Drupal\Tests\file\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upload field instance migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUploadInstanceTest extends MigrateDrupal6TestBase {

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
   * Tests the Drupal 6 upload settings to Drupal 8 field instance migration.
   */
  public function testUploadFieldInstance() {
    $field = FieldConfig::load('node.page.upload');
    $settings = $field->getSettings();
    $this->assertSame('node.page.upload', $field->id());
    $this->assertSame('jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp', $settings['file_extensions']);
    $this->assertSame('1MB', $settings['max_filesize']);
    $this->assertTrue($settings['description_field']);

    $field = FieldConfig::load('node.story.upload');
    $this->assertSame('node.story.upload', $field->id());

    // Shouldn't exist.
    $field = FieldConfig::load('node.article.upload');
    $this->assertNull($field);

    $this->assertSame([['node', 'page', 'upload']], $this->getMigration('d6_upload_field_instance')->getIdMap()->lookupDestinationIds(['page']));
  }

}

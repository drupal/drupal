<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Uploads migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUploadFieldTest extends MigrateDrupal6TestBase {

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
   * Tests the Drupal 6 upload settings to Drupal 8 field migration.
   */
  public function testUpload(): void {
    $field_storage = FieldStorageConfig::load('node.upload');
    $this->assertSame('node.upload', $field_storage->id());
    $this->assertSame([['node', 'upload']], $this->getMigration('d6_upload_field')->getIdMap()->lookupDestinationIds(['']));
  }

}

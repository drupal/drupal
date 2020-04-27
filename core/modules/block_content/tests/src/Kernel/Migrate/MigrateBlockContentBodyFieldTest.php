<?php

namespace Drupal\Tests\block_content\Kernel\Migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Attaches a body field to the custom block type.
 *
 * @group block_content
 */
class MigrateBlockContentBodyFieldTest extends MigrateDrupal7TestBase {

  public static $modules = ['block', 'block_content', 'filter', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('block_content');
    $this->installConfig(['block_content']);
    $this->executeMigrations([
      'block_content_type',
      'block_content_body_field',
    ]);
  }

  /**
   * Tests the block content body field migration.
   */
  public function testBlockContentBodyFieldMigration() {
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = FieldStorageConfig::load('block_content.body');
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $storage);
    $this->assertIdentical('block_content', $storage->getTargetEntityTypeId());
    $this->assertIdentical(['basic'], array_values($storage->getBundles()));
    $this->assertIdentical('body', $storage->getName());

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load('block_content.basic.body');
    $this->assertInstanceOf(FieldConfigInterface::class, $field);
    $this->assertIdentical('block_content', $field->getTargetEntityTypeId());
    $this->assertIdentical('basic', $field->getTargetBundle());
    $this->assertIdentical('body', $field->getName());
    $this->assertIdentical('Body', $field->getLabel());
  }

}

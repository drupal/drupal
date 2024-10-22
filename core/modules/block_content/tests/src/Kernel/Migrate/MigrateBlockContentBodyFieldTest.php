<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel\Migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Attaches a body field to the block type.
 *
 * @group block_content
 */
class MigrateBlockContentBodyFieldTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content', 'filter', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
  public function testBlockContentBodyFieldMigration(): void {
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = FieldStorageConfig::load('block_content.body');
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $storage);
    $this->assertSame('block_content', $storage->getTargetEntityTypeId());
    $this->assertSame(['basic'], array_values($storage->getBundles()));
    $this->assertSame('body', $storage->getName());

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load('block_content.basic.body');
    $this->assertInstanceOf(FieldConfigInterface::class, $field);
    $this->assertSame('block_content', $field->getTargetEntityTypeId());
    $this->assertSame('basic', $field->getTargetBundle());
    $this->assertSame('body', $field->getName());
    $this->assertSame('Body', $field->getLabel());
  }

}

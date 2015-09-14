<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\Migrate\MigrateBlockContentBodyFieldTest.
 */

namespace Drupal\block_content\Tests\Migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Attaches a body field to the custom block type.
 *
 * @group block_content
 */
class MigrateBlockContentBodyFieldTest extends MigrateDrupal7TestBase {

  static $modules = array('block', 'block_content', 'filter', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['block_content']);
    $this->installEntitySchema('block_content');
    $this->executeMigration('block_content_type');
    $this->executeMigration('block_content_body_field');
  }

  /**
   * Tests the block content body field migration.
   */
  public function testBlockContentBodyFieldMigration() {
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = FieldStorageConfig::load('block_content.body');
    $this->assertTrue($storage instanceof FieldStorageConfigInterface);
    $this->assertIdentical('block_content', $storage->getTargetEntityTypeId());
    $this->assertIdentical(['basic'], array_values($storage->getBundles()));
    $this->assertIdentical('body', $storage->getName());

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load('block_content.basic.body');
    $this->assertTrue($field instanceof FieldConfigInterface);
    $this->assertIdentical('block_content', $field->getTargetEntityTypeId());
    $this->assertIdentical('basic', $field->getTargetBundle());
    $this->assertIdentical('body', $field->getName());
    $this->assertIdentical('Body', $field->getLabel());
  }

}

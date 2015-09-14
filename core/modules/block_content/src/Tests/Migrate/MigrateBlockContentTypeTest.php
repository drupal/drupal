<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\Migrate\MigrateBlockContentTypeTest.
 */

namespace Drupal\block_content\Tests\Migrate;

use Drupal\block_content\BlockContentTypeInterface;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of the basic block content type.
 *
 * @group block_content
 */
class MigrateBlockContentTypeTest extends MigrateDrupal7TestBase {

  static $modules = array('block', 'block_content', 'filter', 'text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['block_content']);
    $this->installEntitySchema('block_content');
    $this->executeMigration('block_content_type');
  }

  /**
   * Tests the block content type migration.
   */
  public function testBlockContentTypeMigration() {
    /** @var \Drupal\block_content\BlockContentTypeInterface $entity */
    $entity = BlockContentType::load('basic');
    $this->assertTrue($entity instanceof BlockContentTypeInterface);
    $this->assertIdentical('Basic', $entity->label());
  }

}

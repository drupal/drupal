<?php

namespace Drupal\Tests\image\Kernel\Migrate\d6;

use Drupal\node\Entity\Node;
use Drupal\Tests\node\Kernel\Migrate\d6\MigrateNodeTestBase;
use Drupal\Tests\file\Kernel\Migrate\d6\FileMigrationTestTrait;

/**
 * Image migration test.
 *
 * This extends the node test, because the D6 fixture has images; they just
 * need to be migrated into D8.
 *
 * @group migrate_drupal_6
 */
class MigrateImageTest extends MigrateNodeTestBase {

  use FileMigrationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpMigratedFiles();
    $this->installSchema('file', ['file_usage']);
    $this->executeMigrations([
      'd6_node',
    ]);
  }

  /**
   * Test image migration from Drupal 6 to 8.
   */
  public function testNode() {
    $node = Node::load(9);
    // Test the image field sub fields.
    $this->assertSame('2', $node->field_test_imagefield->target_id);
    $this->assertSame('Test alt', $node->field_test_imagefield->alt);
    $this->assertSame('Test title', $node->field_test_imagefield->title);
    $this->assertSame('80', $node->field_test_imagefield->width);
    $this->assertSame('60', $node->field_test_imagefield->height);
  }

}

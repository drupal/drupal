<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\Migrate\MigrateBlockContentStubTest.
 */

namespace Drupal\block_content\Tests\Migrate;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\StubTestTrait;

/**
 * Test stub creation for block_content entities.
 *
 * @group block_content
 */
class MigrateBlockContentStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('block_content');
  }

  /**
   * Tests creation of block content stubs with no block_content_type available.
   */
  public function testStubFailure() {
    $entity_id = $this->createStub('block_content');
    $violations = $this->validateStub('block_content', $entity_id);
    $this->assertIdentical(count($violations), 1);
    $this->assertEqual($violations[0]->getMessage(), t('The referenced entity (%type: %id) does not exist.', [
      '%type' => 'block_content_type',
      '%id' => 'block_content',
    ]));
  }

  /**
   * Tests creation of block content stubs when there is a block_content_type.
   */
  public function testStubSuccess() {
    BlockContentType::create([
      'id' => 'test_block_content_type',
      'label' => 'Test block content type',
    ])->save();
    $this->performStubTest('block_content');
  }

}

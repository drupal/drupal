<?php

namespace Drupal\Tests\block_content\Kernel\Migrate;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\migrate\MigrateException;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
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
  protected static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('block_content');
  }

  /**
   * Tests creation of block content stubs with no block_content_type available.
   */
  public function testStubFailure() {
    $message = 'Expected MigrateException thrown when no bundles exist.';
    try {
      $this->createEntityStub('block_content');
      $this->fail($message);
    }
    catch (MigrateException $e) {
      $this->pass($message);
      $this->assertEqual('Stubbing failed, no bundles available for entity type: block_content', $e->getMessage());
    }
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

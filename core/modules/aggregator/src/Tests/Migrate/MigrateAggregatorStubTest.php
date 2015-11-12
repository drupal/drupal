<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\Migrate\MigrateAggregatorStubTest.
 */

namespace Drupal\aggregator\Tests\Migrate;

use Drupal\migrate\MigrateException;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\StubTestTrait;

/**
 * Test stub creation for aggregator feeds and items.
 *
 * @group aggregator
 */
class MigrateAggregatorStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');
  }

  /**
   * Tests creation of aggregator feed stubs.
   */
  public function testFeedStub() {
    $this->performStubTest('aggregator_feed');
  }

  /**
   * Tests creation of aggregator feed items.
   */
  public function testItemStub() {
    try {
      // We expect an exception, because there's no feed to reference.
      $this->performStubTest('aggregator_item');
      $this->fail('Expected exception has not been thrown.');
    }
    catch (MigrateException $e) {
      $this->assertIdentical($e->getMessage(),
        'Stubbing failed, unable to generate value for field fid');
    }

    // The stub should pass when there's a feed to point to.
    $this->createStub('aggregator_feed');
    $this->performStubTest('aggregator_item');
  }

}

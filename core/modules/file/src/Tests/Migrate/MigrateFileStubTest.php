<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Migrate\MigrateFileStubTest.
 */

namespace Drupal\file\Tests\Migrate;

use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\StubTestTrait;

/**
 * Test stub creation for file entities.
 *
 * @group file
 */
class MigrateFileStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
  }

  /**
   * Tests creation of file stubs.
   */
  public function testStub() {
    $this->performStubTest('file');
  }

}

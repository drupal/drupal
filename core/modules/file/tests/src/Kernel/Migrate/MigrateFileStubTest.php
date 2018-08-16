<?php

namespace Drupal\Tests\file\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
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

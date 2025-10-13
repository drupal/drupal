<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Migrate;

use Drupal\migrate_drupal\Tests\StubTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test stub creation for file entities.
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
class MigrateFileStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
  }

  /**
   * Tests creation of file stubs.
   */
  public function testStub(): void {
    $this->performStubTest('file');
  }

}

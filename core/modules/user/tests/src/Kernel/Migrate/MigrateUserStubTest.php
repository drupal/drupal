<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Migrate;

use Drupal\migrate_drupal\Tests\StubTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test stub creation for user entities.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
class MigrateUserStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests creation of user stubs.
   */
  public function testStub(): void {
    $this->performStubTest('user');
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Kernel\Migrate;

use Drupal\migrate_drupal\Tests\StubTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test stub creation for shortcut entities.
 */
#[Group('shortcut')]
#[RunTestsInSeparateProcesses]
class MigrateShortcutStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['shortcut', 'link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('shortcut');
    // Make sure the 'default' shortcut_set is installed.
    $this->installConfig(['shortcut']);
  }

  /**
   * Tests creation of shortcut stubs.
   */
  public function testStub(): void {
    $this->performStubTest('shortcut');
  }

}

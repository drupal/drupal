<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel\Migrate;

use Drupal\migrate_drupal\Tests\StubTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test stub creation for menu link content entities.
 */
#[Group('menu_link_content')]
#[RunTestsInSeparateProcesses]
class MigrateMenuLinkContentStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_link_content', 'link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
  }

  /**
   * Tests creation of menu link content stubs.
   */
  public function testStub(): void {
    $this->performStubTest('menu_link_content');
  }

}

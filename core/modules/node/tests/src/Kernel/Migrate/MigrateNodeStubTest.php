<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel\Migrate;

use Drupal\migrate_drupal\Tests\StubTestTrait;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test stub creation for nodes.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class MigrateNodeStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    // Need at least one node type present.
    NodeType::create([
      'type' => 'testnodetype',
      'name' => 'Test node type',
    ])->save();
  }

  /**
   * Tests creation of node stubs.
   */
  public function testStub(): void {
    $this->performStubTest('node');
  }

}

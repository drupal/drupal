<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\hook_loader_test\Hook\CircularDependencyHooks;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test hook loading.
 */
#[Group('Hook')]
#[RunTestsInSeparateProcesses]
class HookLoaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'hook_loader_test',
  ];

  use HookOrderTestTrait;

  /**
   * Test hook implementation order.
   */
  public function testHookOrder(): void {
    $this->assertSameCallList(
      [
        CircularDependencyHooks::class . '::testHook',
      ],
      \Drupal::moduleHandler()->invokeAll('test_hook'),
    );
  }

}

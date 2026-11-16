<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests that the container is shared between kernel test methods.
 */
#[CoversClass(KernelTestBase::class)]
#[Group('KernelTests')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
class KernelTestSharedContainerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['container_rebuild_test'];

  /**
   * Tests the number of container builds for each iteration of a test.
   */
  #[TestWith([1, 1])]
  #[TestWith([2, 1])]
  #[TestWith([3, 1])]
  public function testContainerRebuilds($iteration, $container_rebuilds): void {
    $this->assertSame($container_rebuilds, $this->container->get('state')->get('container_rebuild_test.count', 0), 'Container rebuilt once, iteration ' . $iteration);
  }

}

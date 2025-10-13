<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the service "search.index".
 */
#[Group('search')]
#[RunTestsInSeparateProcesses]
class SearchIndexTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search'];

  /**
   * Test that the service "search.index" is backend overridable.
   */
  public function testSearchIndexServiceIsBackendOverridable(): void {
    $definition = $this->container->getDefinition('search.index');
    $this->assertTrue($definition->hasTag('backend_overridable'));
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Routing\NullRouteMatch;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\layout_builder\Hook\LayoutBuilderHooks;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests layout_builder_system_breadcrumb_alter().
 */
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class LayoutBuilderBreadcrumbAlterTest extends EntityKernelTestBase {

  /**
   * Check that there are no errors when alter called with null route match.
   */
  public function testBreadcrumbAlterNullRouteMatch(): void {
    $breadcrumb = new Breadcrumb();
    $route_match = new NullRouteMatch();
    $layoutBuilderSystemBreadcrumbAlter = new LayoutBuilderHooks();
    $layoutBuilderSystemBreadcrumbAlter->systemBreadcrumbAlter($breadcrumb, $route_match, []);
  }

}

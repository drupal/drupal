<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Routing\NullRouteMatch;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests layout_builder_system_breadcrumb_alter().
 *
 * @group layout_builder
 */
class LayoutBuilderBreadcrumbAlterTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'layout_discovery',
  ];

  /**
   * Check that there are no errors when alter called with null route match.
   */
  public function testBreadcrumbAlterNullRouteMatch(): void {
    $breadcrumb = new Breadcrumb();
    $route_match = new NullRouteMatch();
    layout_builder_system_breadcrumb_alter($breadcrumb, $route_match, []);
  }

}

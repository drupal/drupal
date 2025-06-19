<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Hook;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderBefore;

/**
 * Menu hook implementations for layout_builder_test.
 */
class LayoutBuilderTestMenuHooks {

  /**
   * Implements hook_system_breadcrumb_alter().
   */
  #[Hook(
    'system_breadcrumb_alter',
    order: new OrderBefore(
      modules: ['layout_builder']
    )
  )]
  public function systemBreadcrumbAlter(Breadcrumb &$breadcrumb, RouteMatchInterface $route_match, array $context): void {
    $breadcrumb->addLink(Link::fromTextAndUrl('External link', Url::fromUri('http://www.example.com')));
  }

}

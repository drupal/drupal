<?php

declare(strict_types=1);

namespace Drupal\menu_test;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A path-based breadcrumb builder can be skipped from applying.
 */
class SkippablePathBasedBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  public function __construct(
    protected BreadcrumbBuilderInterface $pathBasedBreadcrumbBuilder,
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match, ?CacheableMetadata $cacheable_metadata = NULL): bool {
    $query_arg = 'menu_test_skip_breadcrumbs';
    $cacheable_metadata?->addCacheContexts(['url.query_args:' . $query_arg]);
    // Apply unless the query argument is present.
    return !$this->requestStack->getCurrentRequest()->query->has($query_arg);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    return $this->pathBasedBreadcrumbBuilder->build($route_match);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface.
 */

namespace Drupal\Core\Breadcrumb;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines an interface for classes that build breadcrumbs.
 */
interface BreadcrumbBuilderInterface {

  /**
   * Whether this breadcrumb builder should be used to build the breadcrumb.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other builders
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match);

  /**
   * Builds the breadcrumb.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\Core\Link[]
   *   An array of links for the breadcrumb. Returning an empty array will
   *   suppress all breadcrumbs.
   */
  public function build(RouteMatchInterface $route_match);

}

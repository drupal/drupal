<?php

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
   * phpcs:disable Drupal.Commenting
   * @todo Uncomment new method parameters before drupal:12.0.0, see
   *   https://www.drupal.org/project/drupal/issues/3459277.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata
   *   The cacheable metadata to add to if your check varies by or depends
   *   on something. Anything you specify here does not have to be repeated in
   *   the build() method as it will be merged in automatically.
   * phpcs:enable
   *
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other builders
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match /* , CacheableMetadata $cacheable_metadata */);

  /**
   * Builds the breadcrumb.
   *
   * There is no need to add any cacheable metadata that was already added in
   * applies() as that will be automatically added for you.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\Core\Breadcrumb\Breadcrumb
   *   A breadcrumb.
   */
  public function build(RouteMatchInterface $route_match);

}

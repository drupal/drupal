<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface.
 */

namespace Drupal\Core\Breadcrumb;

/**
 * Defines an interface for classes that build breadcrumbs.
 */
interface BreadcrumbBuilderInterface {

  /**
   * Builds the breadcrumb.
   *
   * @param array $attributes
   *   Attributes representing the current page.
   *
   * @return array|null
   *   A render array for the breadcrumbs or NULL to let other builders decide.
   *   Returning empty array will suppress all breadcrumbs.
   */
  public function build(array $attributes);

}

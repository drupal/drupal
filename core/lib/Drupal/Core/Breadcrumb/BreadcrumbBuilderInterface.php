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
   * Whether this breadcrumb builder should be used to build the breadcrumb.
   *
   * @param array $attributes
   *   Attributes representing the current page.
   *
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other builders
   *   decide.
   */
  public function applies(array $attributes);

  /**
   * Builds the breadcrumb.
   *
   * @param array $attributes
   *   Attributes representing the current page.
   *
   * @return array
   *   An array of HTML links for the breadcrumb. Returning an empty array will
   *   suppress all breadcrumbs.
   */
  public function build(array $attributes);

}

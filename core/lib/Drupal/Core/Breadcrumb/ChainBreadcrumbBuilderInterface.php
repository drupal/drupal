<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\ChainBreadcrumbBuilderInterface.
 */

namespace Drupal\Core\Breadcrumb;

/**
 * Defines an interface a chained service that builds the breadcrumb.
 */
interface ChainBreadcrumbBuilderInterface extends BreadcrumbBuilderInterface {

  /**
   * Adds another breadcrumb builder.
   *
   * @param \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $builder
   *   The breadcrumb builder to add.
   * @param int $priority
   *   Priority of the breadcrumb builder.
   */
  public function addBuilder(BreadcrumbBuilderInterface $builder, $priority);

}

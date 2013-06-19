<?php

/**
 * @file
 * Contains \Drupal\system\LegacyBreadcrumbBuilder.
 */

namespace Drupal\system;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;

/**
 * Class to define the legacy breadcrumb builder.
 *
 * @deprecated This will be removed in 8.0. Instead, register a new breadcrumb
 *   builder service.
 *
 * @see \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
 *
 * This breadcrumb builder implements legacy support for the
 * drupal_set_breadcrumb() mechanic.
 * Remove this once drupal_set_breadcrumb() has been eliminated.
 */
class LegacyBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $breadcrumb = drupal_set_breadcrumb();
    if (is_array($breadcrumb)) {
      // $breadcrumb is expected to be an array of rendered breadcrumb links.
      return $breadcrumb;
    }
  }

}

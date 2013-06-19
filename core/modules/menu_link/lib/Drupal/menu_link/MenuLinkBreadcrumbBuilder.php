<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuLinkBreadcrumbBuilder.
 */

namespace Drupal\menu_link;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;

/**
 * Class to define the menu_link breadcrumb builder.
 */
class MenuLinkBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    // @todo Rewrite the implementation.
    // Currently the result always array.
    return menu_get_active_breadcrumb();
  }

}

<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd.
 */

namespace Drupal\menu_ui\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Modifies the 'Add link' local action to add a destination.
 */
class MenuLinkAdd extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // Append the current path as destination to the query string.
    $options['query']['destination'] = drupal_get_destination()['destination'];
    return $options;
  }

}

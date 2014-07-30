<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd.
 */

namespace Drupal\menu_ui\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Modifies the 'Add link' local action to add a destination.
 */
class MenuLinkAdd extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(Request $request) {
    $options = parent::getOptions($request);
    // Append the current path as destination to the query string.
    if ($request->attributes->has(RouteObjectInterface::ROUTE_NAME)) {
      $route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME);
      $raw_variables = array();
      if ($request->attributes->has('_raw_variables')) {
        $raw_variables = $request->attributes->get('_raw_variables')->all();
      }
      // @todo Use RouteMatch instead of Request.
      //   https://www.drupal.org/node/2294157
      $options['query']['destination'] = \Drupal::urlGenerator()->generateFromRoute($route_name, $raw_variables);
    }
    return $options;
  }

}

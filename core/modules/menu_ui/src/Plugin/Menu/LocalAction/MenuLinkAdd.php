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
    if ($request->attributes->has('_system_path')) {
      // @todo: is there a better value to get from the request?
      $options['query']['destination'] = $request->attributes->get('_system_path');
    }
    return $options;
  }

}

<?php

namespace Drupal\menu_ui\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionWithDestination;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteProviderInterface;

/**
 * Modifies the 'Add link' local action to add a destination.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
 *   \Drupal\Core\Menu\LocalActionWithDestination instead.
 *
 * @see https://www.drupal.org/node/3490245
 */
class MenuLinkAdd extends LocalActionWithDestination {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteProviderInterface $route_provider, RedirectDestinationInterface $redirectDestination) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_provider, $redirectDestination);

    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Menu\LocalActionWithDestination instead. See https://www.drupal.org/node/3490245', E_USER_DEPRECATED);
  }

}

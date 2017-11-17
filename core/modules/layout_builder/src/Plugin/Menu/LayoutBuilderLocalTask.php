<?php

namespace Drupal\layout_builder\Plugin\Menu;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides route parameters needed to link to layout related tabs.
 *
 * @internal
 */
class LayoutBuilderLocalTask extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);

    // @todo Revisit this code once https://www.drupal.org/node/2912363 is in.
    $parameters['entity'] = $route_match->getParameter('entity');
    return $parameters;
  }

}

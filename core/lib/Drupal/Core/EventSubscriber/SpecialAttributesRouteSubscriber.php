<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a route subscriber which checks for invalid pattern variables.
 */
class SpecialAttributesRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $special_variables = [
      'system_path',
      '_legacy',
      '_raw_variables',
      RouteObjectInterface::ROUTE_OBJECT,
      RouteObjectInterface::ROUTE_NAME,
      '_content',
      '_controller',
      '_form',
    ];
    foreach ($collection->all() as $name => $route) {
      if ($not_allowed_variables = array_intersect($route->compile()->getVariables(), $special_variables)) {
        $reserved = implode(', ', $not_allowed_variables);
        throw new \InvalidArgumentException(sprintf('Route %s uses reserved variable names: %s', $name, $reserved));
      }
    }
  }

  /**
   * Delegates the route altering to self::alterRoutes().
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onAlterRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $this->alterRoutes($collection);
  }

}

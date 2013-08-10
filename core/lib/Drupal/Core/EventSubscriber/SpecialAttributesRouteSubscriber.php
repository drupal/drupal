<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\SpecialAttributesRouteSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Utility\String;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a route subscriber which checks for invalid pattern variables.
 */
class SpecialAttributesRouteSubscriber implements EventSubscriberInterface {

  /**
   * Checks for invalid pattern variables.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event containing the build routes.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a reserved variable was used as route variable.
   */
  public function onRouteBuilding(RouteBuildEvent $event) {
    $special_variables = array(
      '_account',
      'system_path',
      '_maintenance',
      '_legacy',
      '_authentication_provider',
      '_raw_variables',
      RouteObjectInterface::ROUTE_OBJECT,
      RouteObjectInterface::ROUTE_NAME,
      '_content',
      '_form',
    );

    foreach ($event->getRouteCollection()->all() as $route) {
      if ($not_allowed_variables = array_intersect($route->compile()->getVariables(), $special_variables)) {
        $placeholders = array('@variables' => implode(', ', $not_allowed_variables));
        drupal_set_message(String::format('The following variables are reserved names by drupal: @variables', $placeholders));
        watchdog('error', 'The following variables are reserved names by drupal: @variables', $placeholders);
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = 'onRouteBuilding';
    return $events;
  }

}

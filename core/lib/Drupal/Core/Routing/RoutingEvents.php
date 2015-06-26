<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RoutingEvents.
 */

namespace Drupal\Core\Routing;

/**
 * Contains all events thrown in the core routing component.
 */
final class RoutingEvents {

  /**
   * Name of the event fired during route collection to allow new routes.
   *
   * This event is used to add new routes based upon existing routes, giving
   * modules the opportunity to dynamically generate additional routes. The
   * event listener method receives a \Drupal\Core\Routing\RouteBuildEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Routing\RouteBuildEvent
   * @see \Drupal\Core\EventSubscriber\EntityRouteProviderSubscriber
   * @see \Drupal\Core\Routing\RouteBuilder::rebuild()
   *
   * @var string
   */
  const DYNAMIC = 'routing.route_dynamic';

  /**
   * Name of the event fired during route collection to allow changes to routes.
   *
   * This event is used to process new routes before they get saved, giving
   * modules the opportunity to alter routes provided by any other module. The
   * event listener method receives a \Drupal\Core\Routing\RouteBuildEvent
   * instance.
   *
   * @Event
   *
   * @see \Symfony\Component\Routing\RouteCollection
   * @see \Drupal\system\EventSubscriber\AdminRouteSubscriber
   * @see \Drupal\Core\Routing\RouteBuilder::rebuild()
   *
   * @var string
   */
  const ALTER = 'routing.route_alter';

  /**
   * Name of the event fired to indicate route building has ended.
   *
   * This event gives modules the opportunity to perform some action after route
   * building has completed. The event listener receives a
   * \Symfony\Component\EventDispatcher\Event instance.
   *
   * @Event
   *
   * @see \Symfony\Component\EventDispatcher\Event
   * @see \Drupal\Core\EventSubscriber\MenuRouterRebuildSubscriber
   * @see \Drupal\Core\Routing\RouteBuilder::rebuild()
   *
   * @var string
   */
  const FINISHED = 'routing.route_finished';

}

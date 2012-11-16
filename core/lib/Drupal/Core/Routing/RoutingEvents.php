<?php

/**
 * @file
 * Definition of \Drupal\Core\Routing\RoutingEvents.
 */

namespace Drupal\Core\Routing;

/**
 * Contains all events thrown in the core routing component.
 */
final class RoutingEvents {

  /**
   * The ALTER event is fired on a route collection to allow changes to routes.
   *
   * This event is used to process new routes before they get saved.
   *
   * @see \Drupal\Core\Routing\RouteBuildEvent
   *
   * @var string
   */
  const ALTER = 'routing.route_alter';

  /**
   * The DYNAMIC event is fired to allow modules to register additional routes.
   *
   * Most routes are static, an should be defined as such. Dynamic routes are
   * only those whose existence changes depending on the state of the system
   * at runtime, depending on configuration.
   *
   * @see \Drupal\Core\Routing\RouteBuildEvent
   *
   * @var string
   */
  const DYNAMIC = 'routing.route_dynamic';
}

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
   * The FINISHED event is fired when the route building ended.
   */
  const FINISHED = 'routing.route_finished';

}

<?php

namespace Drupal\big_pipe\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteBuildEvent;

/**
 * Sets the '_no_big_pipe' option on select routes.
 */
class NoBigPipeRouteAlterSubscriber implements EventSubscriberInterface {

  /**
   * Alters select routes to have the '_no_big_pipe' option.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingRouteAlterSetNoBigPipe(RouteBuildEvent $event) {
    $no_big_pipe_routes = [
      // The batch system uses a <meta> refresh to work without JavaScript.
      'system.batch_page.html',
      // When a user would install the BigPipe module using a browser and with
      // JavaScript disabled, the first response contains the status messages
      // for installing a module, but then the BigPipe no-JS redirect occurs,
      // which then causes the user to not see those status messages.
      // @see https://www.drupal.org/node/2469431#comment-10901944
      'system.modules_list',
    ];

    $route_collection = $event->getRouteCollection();
    foreach ($no_big_pipe_routes as $excluded_route) {
      if ($route = $route_collection->get($excluded_route)) {
        $route->setOption('_no_big_pipe', TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = ['onRoutingRouteAlterSetNoBigPipe'];
    return $events;
  }

}

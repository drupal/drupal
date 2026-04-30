<?php

namespace Drupal\rest\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replace the deprecated user module routes with our own.
 */
class DeprecatedUserRoutesSubscriber implements EventSubscriberInterface {

  /**
   * Replace the deprecated controllers on user.module's routes with ours.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onRouteAlter(RouteBuildEvent $event): void {
    foreach (['pass', 'login', 'login_status', 'logout'] as $route_name) {
      $user_route = $event->getRouteCollection()->get("user.$route_name.http");
      $rest_route = $event->getRouteCollection()->get("rest.$route_name");
      if ($user_route && $rest_route) {
        $user_route->setDefault('_controller', $rest_route->getDefault('_controller'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[RoutingEvents::ALTER] = 'onRouteAlter';
    return $events;
  }

}

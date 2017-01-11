<?php

namespace Drupal\serialization\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters user authentication routes to support additional serialization formats.
 */
class UserRouteAlterSubscriber implements EventSubscriberInterface {

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = [];

  /**
   * UserRouteAlterSubscriber constructor.
   *
   * @param array $serializer_formats
   *   The available serializer formats.
   */
  public function __construct(array $serializer_formats) {
    $this->serializerFormats = $serializer_formats;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = 'onRoutingAlterAddFormats';
    return $events;
  }

  /**
   * Adds supported formats to the user authentication HTTP routes.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingAlterAddFormats(RouteBuildEvent $event) {
    $route_names = [
      'user.login_status.http',
      'user.login.http',
      'user.logout.http',
    ];
    $routes = $event->getRouteCollection();
    foreach ($route_names as $route_name) {
      if ($route = $routes->get($route_name)) {
        $formats = explode('|', $route->getRequirement('_format'));
        $formats = array_unique(array_merge($formats, $this->serializerFormats));
        $route->setRequirement('_format', implode('|', $formats));
      }
    }
  }

}

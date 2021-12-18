<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Ensures that routes can be provided by entity types.
 */
class EntityRouteProviderSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityRouteProviderSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Provides routes on route rebuild time.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onDynamicRouteEvent(RouteBuildEvent $event) {
    $route_collection = $event->getRouteCollection();
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->hasRouteProviders()) {
        foreach ($this->entityTypeManager->getRouteProviders($entity_type->id()) as $route_provider) {
          // Allow to both return an array of routes or a route collection,
          // like route_callbacks in the routing.yml file.

          $routes = $route_provider->getRoutes($entity_type);
          if ($routes instanceof RouteCollection) {
            $routes = $routes->all();
          }
          foreach ($routes as $route_name => $route) {
            // Don't override existing routes.
            if (!$route_collection->get($route_name)) {
              $route_collection->add($route_name, $route);
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[RoutingEvents::DYNAMIC][] = ['onDynamicRouteEvent'];
    return $events;
  }

}

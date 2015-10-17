<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\EntityRouteProviderSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Ensures that routes can be provided by entity types.
 */
class EntityRouteProviderSubscriber implements EventSubscriberInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityRouteProviderSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Provides routes on route rebuild time.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onDynamicRouteEvent(RouteBuildEvent $event) {
    $route_collection = $event->getRouteCollection();
    foreach ($this->entityManager->getDefinitions() as $entity_type) {
      if ($entity_type->hasRouteProviders()) {
        foreach ($this->entityManager->getRouteProviders($entity_type->id()) as $route_provider) {
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
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC][] = ['onDynamicRouteEvent'];
    return $events;
  }

}


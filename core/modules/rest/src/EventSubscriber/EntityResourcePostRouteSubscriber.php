<?php

namespace Drupal\rest\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates a 'create' route for an entity type if it has a REST POST route.
 */
class EntityResourcePostRouteSubscriber implements EventSubscriberInterface {

  /**
   * The REST resource config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $resourceConfigStorage;

  /**
   * Constructs a new EntityResourcePostRouteSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->resourceConfigStorage = $entity_type_manager->getStorage('rest_resource_config');
  }

  /**
   * Provides routes on route rebuild time.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onDynamicRouteEvent(RouteBuildEvent $event) {
    $route_collection = $event->getRouteCollection();

    $resource_configs = $this->resourceConfigStorage->loadMultiple();
    // Iterate over all REST resource config entities.
    foreach ($resource_configs as $resource_config) {
      // We only care about REST resource config entities for the
      // \Drupal\rest\Plugin\rest\resource\EntityResource plugin.
      $plugin_id = $resource_config->toArray()['plugin_id'];
      if (substr($plugin_id, 0, 6) !== 'entity') {
        continue;
      }

      $entity_type_id = substr($plugin_id, 7);
      $rest_post_route_name = "rest.entity.$entity_type_id.POST";
      if ($rest_post_route = $route_collection->get($rest_post_route_name)) {
        // Create a route for the 'create' link relation type for this entity
        // type that uses the same route definition as the REST 'POST' route
        // which use that entity type.
        // @see \Drupal\Core\Entity\Entity::toUrl()
        $entity_create_route_name = "entity.$entity_type_id.create";
        $route_collection->add($entity_create_route_name, $rest_post_route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Priority -10, to run after \Drupal\rest\Routing\ResourceRoutes, which has
    // priority 0.
    $events[RoutingEvents::DYNAMIC][] = ['onDynamicRouteEvent', -10];
    return $events;
  }

}

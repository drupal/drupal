<?php

/**
 * @file
 * Contains Drupal\Core\EventSubscriber\EntityRouteAlterSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteBuildEvent;

/**
 * Registers the 'type' of route parameter names that match an entity type.
 *
 * @todo Matching on parameter *name* is not ideal, because it breaks
 *   encapsulation: parameter names are local to the controller and route, and
 *   controllers and routes can't be expected to know what all possible entity
 *   types might exist across all modules in order to pick names that don't
 *   conflict. Instead, the 'type' should be determined from introspecting what
 *   kind of PHP variable (e.g., a type hinted interface) the controller
 *   requires: https://drupal.org/node/2041907.
 */
class EntityRouteAlterSubscriber implements EventSubscriberInterface {

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityRouteAlterSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Applies parameter converters to route parameters.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingRouteAlterSetType(RouteBuildEvent $event) {
    $entity_types = array_keys($this->entityManager->getDefinitions());
    foreach ($event->getRouteCollection() as $route) {
      $parameter_definitions = $route->getOption('parameters') ?: array();
      // For all route parameter names that match an entity type, add the 'type'
      // to the parameter definition if it's not already explicitly provided.
      foreach (array_intersect($route->compile()->getVariables(), $entity_types) as $parameter_name) {
        if (!isset($parameter_definitions[$parameter_name])) {
          $parameter_definitions[$parameter_name] = array();
        }
        $parameter_definitions[$parameter_name] += array(
          'type' => 'entity:' . $parameter_name,
        );
      }
      if (!empty($parameter_definitions)) {
        $route->setOption('parameters', $parameter_definitions);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = array('onRoutingRouteAlterSetType', -150);
    return $events;
  }
}

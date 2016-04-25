<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Entity\EntityResolverManager;
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
 *   requires: https://www.drupal.org/node/2041907.
 */
class EntityRouteAlterSubscriber implements EventSubscriberInterface {

  /**
   * The entity resolver manager.
   *
   * @var \Drupal\Core\Entity\EntityResolverManager
   */
  protected $resolverManager;

  /**
   * Constructs an EntityRouteAlterSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityResolverManager
   *   The entity resolver manager.
   */
  public function __construct(EntityResolverManager $entity_resolver_manager) {
    $this->resolverManager = $entity_resolver_manager;
  }

  /**
   * Applies parameter converters to route parameters.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingRouteAlterSetType(RouteBuildEvent $event) {
    foreach ($event->getRouteCollection() as $route) {
      $this->resolverManager->setRouteOptions($route);
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

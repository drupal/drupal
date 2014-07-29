<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\AccessRouteSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a subscriber to set access checkers on route building.
 */
class AccessRouteSubscriber implements EventSubscriberInterface {

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Constructs a new AccessSubscriber.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access check manager that will be responsible for applying
   *   AccessCheckers against routes.
   */
  public function __construct(AccessManagerInterface $access_manager) {
    $this->accessManager = $access_manager;
  }

  /**
   * Apply access checks to routes.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingRouteAlterSetAccessCheck(RouteBuildEvent $event) {
    $this->accessManager->setChecks($event->getRouteCollection());
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    // Setting very low priority to ensure access checks are run after alters.
    $events[RoutingEvents::ALTER][] = array('onRoutingRouteAlterSetAccessCheck', -1000);

    return $events;
  }

}

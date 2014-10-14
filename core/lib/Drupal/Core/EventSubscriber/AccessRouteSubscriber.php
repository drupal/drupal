<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\AccessRouteSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Access\CheckProviderInterface;
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
   * @var \Drupal\Core\Access\checkProviderInterface
   */
  protected $checkProvider;

  /**
   * Constructs a new AccessSubscriber.
   *
   * @param \Drupal\Core\Access\CheckProviderInterface $check_provider
   *   The check provider that will be responsible for applying
   *   access checkers against routes.
   */
  public function __construct(CheckProviderInterface $check_provider) {
    $this->checkProvider = $check_provider;
  }

  /**
   * Apply access checks to routes.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingRouteAlterSetAccessCheck(RouteBuildEvent $event) {
    $this->checkProvider->setChecks($event->getRouteCollection());
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

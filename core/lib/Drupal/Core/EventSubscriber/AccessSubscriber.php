<?php

/**
 * @file
 * Contains Drupal\Core\EventSubscriber\AccessSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\Routing\RouteBuildEvent;

/**
 * Access subscriber for controller requests.
 */
class AccessSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new AccessSubscriber.
   *
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The access check manager that will be responsible for applying
   *   AccessCheckers against routes.
   */
  public function __construct(AccessManager $access_manager) {
    $this->accessManager = $access_manager;
  }

  /**
   * Verifies that the current user can access the requested path.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestAccessCheck(GetResponseEvent $event) {
    $request = $event->getRequest();
    if (!$request->attributes->has(RouteObjectInterface::ROUTE_OBJECT)) {
      // If no Route is available it is likely a static resource and access is
      // handled elsewhere.
      return;
    }

    $access = $this->accessManager->check($request->attributes->get(RouteObjectInterface::ROUTE_OBJECT), $request);
    if (!$access) {
      throw new AccessDeniedHttpException();
    }
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
    $events[KernelEvents::REQUEST][] = array('onKernelRequestAccessCheck', 30);
    // Setting very low priority to ensure access checks are run after alters.
    $events[RoutingEvents::ALTER][] = array('onRoutingRouteAlterSetAccessCheck', -50);

    return $events;
  }
}

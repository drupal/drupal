<?php

/**
 * @file
 * Contains Drupal\Core\EventSubscriber\AccessSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Access subscriber for controller requests.
 */
class AccessSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccessManagerInterface $access_manager, AccountInterface $current_user) {
    $this->accessManager = $access_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Verifies that the current user can access the requested path.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access got denied.
   */
  public function onKernelRequestAccessCheck(GetResponseEvent $event) {
    $request = $event->getRequest();

    // The controller is being handled by the HTTP kernel, so add an attribute
    // to tell us this is the controller request.
    $request->attributes->set('_controller_request', TRUE);

    if (!$request->attributes->has(RouteObjectInterface::ROUTE_OBJECT)) {
      // If no Route is available it is likely a static resource and access is
      // handled elsewhere.
      return;
    }

    // Wrap this in a try/catch to ensure the '_controller_request' attribute
    // can always be removed.
    try {
      $access = $this->accessManager->check($request->attributes->get(RouteObjectInterface::ROUTE_OBJECT), $request, $this->currentUser);
    }
    catch (\Exception $e) {
      $request->attributes->remove('_controller_request');
      throw $e;
    }

    $request->attributes->remove('_controller_request');

    if (!$access) {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $current_user
   *  The current user service.
   */
  public function setCurrentUser(AccountInterface $current_user = NULL) {
    $this->currentUser = $current_user;
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestAccessCheck', 30);

    return $events;
  }

}

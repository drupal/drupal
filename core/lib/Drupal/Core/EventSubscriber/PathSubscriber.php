<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @file
 *
 * Definition of Drupal\Core\EventSubscriber\AccessSubscriber
 */

/**
 * Access subscriber for controller requests.
 */
class PathSubscriber implements EventSubscriberInterface {

  /**
   * Resolve the system path.
   *
   * @todo The path system should be objectified to remove the function calls
   * in this method.
   *
   * @todo We're writing back to $_GET['q'] for temporary BC. All instances of
   * $_GET['q'] should be removed and then this code eliminated.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestPathResolve(GetResponseEvent $event) {

    $request = $event->getRequest();

    $path = ltrim($request->getPathInfo(), '/');

    if (empty($path)) {
      // @todo Temporary hack. Fix when configuration is injectable.
      $path = variable_get('site_frontpage', 'user');
    }
    $system_path = drupal_get_normal_path($path);

    $request->attributes->set('system_path', $system_path);

    // @todo Remove this line once code has been refactored to use the request
    // object directly.
    _current_path($system_path);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestPathResolve', 100);

    return $events;
  }
}

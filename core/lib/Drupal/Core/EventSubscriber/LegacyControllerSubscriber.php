<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\LegacyControllerSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Access subscriber for controller requests.
 */
class LegacyControllerSubscriber implements EventSubscriberInterface {

  /**
   * Wraps legacy controllers in a closure to handle old-style arguments.
   *
   * This is a backward compatibility layer only.  This is a rather ugly way
   * to piggyback Drupal's existing menu router items onto the Symfony model,
   * but it works for now.  If we did not do this, any menu router item with
   * a variable number of arguments would fail to work.  This bypasses Symfony's
   * controller argument handling entirely and lets the old-style approach work.
   *
   * @todo Convert Drupal to use the IETF-draft-RFC style {placeholders}. That
   *   will allow us to use the native Symfony conversion, including
   *   out-of-order argument mapping, name-based mapping, and with another
   *   listener auto-conversion of parameters to full objects. That may
   *   necessitate not using func_get_args()-based controllers. That is likely
   *   for the best, as those are quite hard to document anyway.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   *   The Event to process.
   */
  public function onKernelControllerLegacy(FilterControllerEvent $event) {
    $router_item = $event->getRequest()->attributes->get('drupal_menu_item');
    $controller = $event->getController();

    // This BC logic applies only to functions. Otherwise, skip it.
    if (is_string($controller) && function_exists($controller)) {
      $new_controller = function() use ($router_item) {
        return call_user_func_array($router_item['page_callback'], $router_item['page_arguments']);
      };
      $event->setController($new_controller);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::CONTROLLER][] = array('onKernelControllerLegacy', 30);

    return $events;
  }
}

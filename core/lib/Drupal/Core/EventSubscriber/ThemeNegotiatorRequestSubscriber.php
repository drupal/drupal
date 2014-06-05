<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ThemeNegotiatorRequestSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Initializes the theme for the current request.
 */
class ThemeNegotiatorRequestSubscriber implements EventSubscriberInterface {

  /**
   * Initializes the theme system after the routing system.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestThemeNegotiator(GetResponseEvent $event) {
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      if (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') {
        // @todo Refactor drupal_theme_initialize() into a request subscriber.
        // @see https://drupal.org/node/2228093
        drupal_theme_initialize($event->getRequest());
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestThemeNegotiator', 29);
    return $events;
  }

}

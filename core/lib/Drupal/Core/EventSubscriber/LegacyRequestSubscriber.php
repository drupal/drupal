<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\LegacyRequestSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * KernelEvents::REQUEST event subscriber to initialize theme and modules.
 *
 * @todo Remove this subscriber when all of the code in it has been refactored.
 */
class LegacyRequestSubscriber implements EventSubscriberInterface {

  /**
   * Initializes the rest of the legacy Drupal subsystems.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestLegacy(GetResponseEvent $event) {
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      // Tell Drupal it is now fully bootstrapped (for the benefit of code that
      // calls drupal_get_bootstrap_phase()), but without having
      // _drupal_bootstrap_full() do anything, since we've already done the
      // equivalent above and in earlier listeners.
      _drupal_bootstrap_full(TRUE);
      drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    }
  }

  /**
   * Initializes the theme system after the routing system.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestLegacyAfterRouting(GetResponseEvent $event) {
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      drupal_theme_initialize();
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestLegacy', 90);
    // Initialize the theme system after the routing system.
    $events[KernelEvents::REQUEST][] = array('onKernelRequestLegacyAfterRouting', 30);

    return $events;
  }
}

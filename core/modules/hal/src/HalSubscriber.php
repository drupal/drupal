<?php

/**
 * @file
 * Contains \Drupal\hal\HalSubscriber.
 */

namespace Drupal\hal;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the kernel request event to add HAL media types.
 */
class HalSubscriber implements EventSubscriberInterface {

  /**
   * Registers HAL formats with the Request class.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $request->setFormat('hal_json', 'application/hal+json');
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest', 40);
    return $events;
  }

}

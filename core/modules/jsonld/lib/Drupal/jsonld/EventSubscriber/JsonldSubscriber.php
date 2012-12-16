<?php

/**
 * @file
 * Contains Drupal\jsonld\EventSubscriber\JsonldSubscriber.
 */

namespace Drupal\jsonld\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the kernel request event to add JSON-LD media formats.
 */
class JsonldSubscriber implements EventSubscriberInterface {

  /**
   * Registers JSON-LD formats with the Request class.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $request->setFormat('drupal_jsonld', 'application/vnd.drupal.ld+json');
    $request->setFormat('jsonld', 'application/ld+json');
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

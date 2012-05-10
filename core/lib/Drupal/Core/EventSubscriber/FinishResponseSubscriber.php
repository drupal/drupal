<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\FinishResponseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to handle finished responses.
 */
class FinishResponseSubscriber implements EventSubscriberInterface {

  /**
   * Sets extra headers on successful responses.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();

    // Set the X-UA-Compatible HTTP header to force IE to use the most recent
    // rendering engine or use Chrome's frame rendering engine if available.
    $response->headers->set('X-UA-Compatible', 'IE=edge,chrome=1', false);

    // Set the Content-language header.
    $response->headers->set('Content-language', drupal_container()->get(LANGUAGE_TYPE_INTERFACE)->langcode);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onRespond');
    return $events;
  }
}

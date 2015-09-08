<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ContentControllerSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the request format onto the request object.
 *
 * @todo Remove this event subscriber after
 *   https://www.drupal.org/node/2092647 has landed.
 */
class ContentControllerSubscriber implements EventSubscriberInterface {

  /**
   * Sets the _controller on a request when a _form is defined.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequestDeriveFormWrapper(GetResponseEvent $event) {
    $request = $event->getRequest();

    if ($request->attributes->has('_form')) {
      $request->attributes->set('_controller', 'controller.form:getContentResult');
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequestDeriveFormWrapper', 25);

    return $events;
  }

}

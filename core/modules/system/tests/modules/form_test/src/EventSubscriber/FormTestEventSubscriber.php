<?php

/**
 * @file
 * Contains \Drupal\form_test\EventSubscriber\FormTestEventSubscriber.
 */

namespace Drupal\form_test\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test event subscriber to add new attributes to the request.
 */
class FormTestEventSubscriber implements EventSubscriberInterface {

  /**
   * Adds custom attributes to the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The kernel request event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $request->attributes->set('custom_attributes', 'custom_value');
    $request->attributes->set('request_attribute', 'request_value');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest');
    return $events;
  }

}

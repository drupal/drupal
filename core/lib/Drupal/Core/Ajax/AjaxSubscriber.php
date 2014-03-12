<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\AjaxSubscriber.
 */

namespace Drupal\Core\Ajax;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the kernel request event to add the Ajax media type.
 *
 * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
 *   The event to process.
 */
class AjaxSubscriber implements EventSubscriberInterface {

  /**
   * Registers Ajax formats with the Request class.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $request->setFormat('drupal_ajax', 'application/vnd.drupal-ajax');
    $request->setFormat('drupal_dialog', 'application/vnd.drupal-dialog');
    $request->setFormat('drupal_modal', 'application/vnd.drupal-modal');
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents(){
    $events[KernelEvents::REQUEST][] = array('onKernelRequest', 50);
    return $events;
  }

}
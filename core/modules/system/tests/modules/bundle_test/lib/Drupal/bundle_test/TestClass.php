<?php

/**
 * @file
 * Definition of Drupal\bundle_test\TestClass.
 */

namespace Drupal\bundle_test;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestClass implements EventSubscriberInterface {

  /**
   * A simple kernel listener method.
   */
  public function onKernelRequestTest(GetResponseEvent $event) {
    drupal_set_message(t('The bundle_test event subscriber fired!'));
  }

  /**
   * Registers methods as kernel listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestTest', 100);
    return $events;
  }
}

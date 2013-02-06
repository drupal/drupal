<?php

/**
 * @file
 * Contains \Drupal\session_test\EventSubscriber\SessionTestSubscriber.
 */

namespace Drupal\session_test\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a test session subscriber that checks whether the session is empty.
 */
class SessionTestSubscriber implements EventSubscriberInterface {

  /*
   * Stores whether $_SESSION is empty at the beginning of the request.
   */
  protected $emptySession;

  /**
   * Set header for session testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestSessionTest(GetResponseEvent $event) {
    $this->emptySession = intval(empty($_SESSION));
  }

  /**
   * Set header for session testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Event to process.
   */
  public function onKernelResponseSessionTest(FilterResponseEvent $event) {
    $event->getResponse()->headers->set('X-Session-Empty', $this->emptySession);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onKernelResponseSessionTest', 300);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestSessionTest', 300);
    return $events;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\session_test\EventSubscriber\SessionTestSubscriber.
 */

namespace Drupal\session_test\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Defines a test session subscriber that checks whether the session is empty.
 */
class SessionTestSubscriber implements EventSubscriberInterface {

  /**
   * Stores whether $_SESSION is empty at the beginning of the request.
   *
   * @var bool
   */
  protected $emptySession;

  /**
   * Set header for session testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestSessionTest(GetResponseEvent $event) {
    $session = $event->getRequest()->getSession();
    $this->emptySession = (int) !($session && $session->start());
  }

  /**
   * Performs tasks for session_test module on kernel.response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Event to process.
   */
  public function onKernelResponseSessionTest(FilterResponseEvent $event) {
    // Set header for session testing.
    $response = $event->getResponse();
    $response->headers->set('X-Session-Empty', $this->emptySession);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onKernelResponseSessionTest');
    $events[KernelEvents::REQUEST][] = array('onKernelRequestSessionTest');
    return $events;
  }

}

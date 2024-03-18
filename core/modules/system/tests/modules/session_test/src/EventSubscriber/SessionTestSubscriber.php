<?php

namespace Drupal\session_test\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Defines a test session subscriber that checks whether the session is empty.
 */
class SessionTestSubscriber implements EventSubscriberInterface {

  /**
   * Stores whether the session is empty at the beginning of the request.
   *
   * @var bool
   */
  protected $emptySession;

  /**
   * Set header for session testing.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The Event to process.
   */
  public function onKernelRequestSessionTest(RequestEvent $event) {
    $session = $event->getRequest()->getSession();
    $this->emptySession = (int) !($session && $session->start());
  }

  /**
   * Performs tasks for session_test module on kernel.response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The Event to process.
   */
  public function onKernelResponseSessionTest(ResponseEvent $event) {
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
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['onKernelResponseSessionTest'];
    $events[KernelEvents::REQUEST][] = ['onKernelRequestSessionTest'];
    return $events;
  }

}

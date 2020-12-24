<?php

namespace Drupal\router_test;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscribers for testing routing when exceptions are thrown in early
 * kernel middleware.
 */
class RouterTestEarlyExceptionSubscriber implements EventSubscriberInterface {

  /**
   * Throw an exception, which will trigger exception-handling subscribers
   * in core, namely DefaultExceptionHtmlSubscriber.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    if ($event->isMasterRequest() && $event->getRequest()->headers->get('Authorization') === 'Bearer invalid') {
      throw new HttpException(
        Response::HTTP_UNAUTHORIZED,
        'This is a common exception during authentication.'
      );
    }
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    // This is the same priority as AuthenticationSubscriber, however
    // exceptions are not restricted to authentication; this is a common,
    // early point to emulate an exception, e.g. when an OAuth token is
    // rejected.
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 300];
    return $events;
  }

}

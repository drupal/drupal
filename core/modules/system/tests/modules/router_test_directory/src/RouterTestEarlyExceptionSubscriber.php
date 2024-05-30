<?php

declare(strict_types=1);

namespace Drupal\router_test;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscribers for exceptions thrown in early kernel middleware.
 */
class RouterTestEarlyExceptionSubscriber implements EventSubscriberInterface {

  /**
   * Throw an exception, which will trigger exception-handling subscribers.
   *
   * See DefaultExceptionHtmlSubscriber.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if ($event->isMainRequest() && $event->getRequest()->headers->get('Authorization') === 'Bearer invalid') {
      throw new HttpException(
        Response::HTTP_UNAUTHORIZED,
        'This is a common exception during authentication.'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // This is the same priority as AuthenticationSubscriber, however
    // exceptions are not restricted to authentication; this is a common,
    // early point to emulate an exception, e.g. when an OAuth token is
    // rejected.
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 300];
    return $events;
  }

}

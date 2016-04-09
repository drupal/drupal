<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * View subscriber rendering a 406 if we could not route or render a request.
 *
 * @todo fix or replace this in https://www.drupal.org/node/2364011
 */
class AcceptNegotiation406 implements EventSubscriberInterface {

  /**
   * Throws an HTTP 406 error if we get this far, which we normally shouldn't.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The event to process.
   */
  public function onViewDetect406(GetResponseForControllerResultEvent $event) {
    $request = $event->getRequest();
    $result = $event->getControllerResult();

    // If this is a render array then we assume that the router went with the
    // generic controller and not one with a format. If the format requested is
    // not HTML though we can also assume that the requested format is invalid
    // so we provide a 406 response.
    if (is_array($result) && $request->getRequestFormat() !== 'html') {
      throw new NotAcceptableHttpException('Not acceptable format: ' . $request->getRequestFormat());
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onViewDetect406', -10];

    return $events;
  }

}

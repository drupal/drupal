<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Throws 406 if requesting non-HTML format and controller returns render array.
 */
class RenderArrayNonHtmlSubscriber implements EventSubscriberInterface {

  /**
   * Throws an HTTP 406 error if client requested a non-HTML format.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The event to process.
   */
  public function onRespond(GetResponseForControllerResultEvent $event) {
    $request = $event->getRequest();
    $result = $event->getControllerResult();

    // If this is a render array then we assume that the router went with the
    // generic controller and not one with a format. If the format requested is
    // not HTML though, we can also assume that the requested format is invalid
    // so we provide a 406 response.
    if (is_array($result) && $request->getRequestFormat() !== 'html') {
      throw new NotAcceptableHttpException('Not acceptable format: ' . $request->getRequestFormat());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onRespond', -10];
    return $events;
  }

}

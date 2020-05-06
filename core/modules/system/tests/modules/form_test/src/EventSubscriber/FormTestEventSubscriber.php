<?php

namespace Drupal\form_test\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test event subscriber to add new attributes to the request.
 */
class FormTestEventSubscriber implements EventSubscriberInterface {

  /**
   * Adds custom attributes to the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The kernel request event.
   */
  public function onKernelRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $request->attributes->set('custom_attributes', 'custom_value');
    $request->attributes->set('request_attribute', 'request_value');
  }

  /**
   * Adds custom headers to the response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The kernel response event.
   */
  public function onKernelResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->set('X-Form-Test-Response-Event', 'invoked');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse'];
    return $events;
  }

}

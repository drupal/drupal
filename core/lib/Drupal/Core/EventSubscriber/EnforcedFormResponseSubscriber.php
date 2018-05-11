<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Form\EnforcedResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handle the EnforcedResponseException and deliver an EnforcedResponse.
 */
class EnforcedFormResponseSubscriber implements EventSubscriberInterface {

  /**
   * Replaces the response in case an EnforcedResponseException was thrown.
   */
  public function onKernelException(GetResponseForExceptionEvent $event) {
    if ($response = EnforcedResponse::createFromException($event->getException())) {
      // Setting the response stops the event propagation.
      $event->setResponse($response);
    }
  }

  /**
   * Unwraps an enforced response.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof EnforcedResponse && $event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
      $event->setResponse($response->getResponse());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION] = ['onKernelException', 128];
    $events[KernelEvents::RESPONSE] = ['onKernelResponse', 128];

    return $events;
  }

}

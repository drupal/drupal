<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds "Permissions-Policy: interest-cohort=()" header to block Google FLoC.
 */
class InterestCohortBlockerSubscriber implements EventSubscriberInterface {

  /**
   * Adds "Permissions-Policy: interest-cohort=()" header.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event.
   */
  public function onKernelResponse(ResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    if (!Settings::get('block_interest_cohort', TRUE)) {
      return;
    }

    $event->getResponse()->headers->set('Permissions-Policy', 'interest-cohort=()');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => ['onKernelResponse']];
  }

}

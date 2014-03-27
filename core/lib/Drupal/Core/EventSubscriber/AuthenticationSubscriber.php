<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\AuthenticationSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Authentication subscriber.
 *
 * Trigger authentication and cleanup during the request.
 */
class AuthenticationSubscriber implements EventSubscriberInterface {

  /**
   * Authentication provider.
   *
   * @var AuthenticationProviderInterface
   */
  protected $authenticationProvider;

  /**
   * Keep authentication manager as private variable.
   *
   * @param AuthenticationProviderInterface $authentication_manager
   *   The authentication manager.
   */
  public function __construct(AuthenticationProviderInterface $authentication_provider) {
    $this->authenticationProvider = $authentication_provider;
  }

  /**
   * Triggers authentication clean up on response.
   *
   * @see \Drupal\Core\Authentication\AuthenticationProviderInterface::cleanup()
   */
  public function onRespond(FilterResponseEvent $event) {
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      $request = $event->getRequest();
      $this->authenticationProvider->cleanup($request);
    }
  }

  /**
   * Pass exception handling to authentication manager.
   *
   * @param GetResponseForExceptionEvent $event
   */
  public function onException(GetResponseForExceptionEvent $event) {
    if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
      $this->authenticationProvider->handleException($event);
    }
  }

  /**
   * {@inheritdoc}
   *
   * The priority for request must be higher than the highest event subscriber
   * accessing the current user.
   * The priority for the response must be as low as possible allowing e.g the
   * Cookie provider to send all relevant session data to the user.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onRespond', 0);
    $events[KernelEvents::EXCEPTION][] = array('onException', 0);
    return $events;
  }
}

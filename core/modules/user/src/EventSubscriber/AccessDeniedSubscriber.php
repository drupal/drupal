<?php

/**
 * @file
 * Contains \Drupal\user\EventSubscriber\AccessDeniedSubscriber.
 */

namespace Drupal\user\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects anonymous users from user.page to user.login.
 */
class AccessDeniedSubscriber implements EventSubscriberInterface {

  use UrlGeneratorTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new redirect subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(AccountInterface $account, URLGeneratorInterface $url_generator) {
    $this->account = $account;
    $this->setUrlGenerator($url_generator);
  }

  /**
   * Redirects anonymous users from user.page to user.login.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $route_name = RouteMatch::createFromRequest($event->getRequest())->getRouteName();
      if ($route_name == 'user.page' && !$this->account->isAuthenticated()) {
        $event->setResponse($this->redirect('user.login'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Use a higher priority than
    // \Drupal\Core\EventSubscriber\ExceptionLoggingSubscriber, because there's
    // no need to log the exception if we can redirect.
    $events[KernelEvents::EXCEPTION][] = ['onException', 75];
    return $events;
  }

}

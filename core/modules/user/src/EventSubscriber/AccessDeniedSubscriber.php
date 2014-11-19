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
 * Redirects users when access is denied.
 *
 * Anonymous users are taken to the login page when attempting to access the
 * user profile pages. Authenticated users are redirected from the login form to
 * their profile page and from the user registration form to their profile edit
 * form.
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
   * Redirects users when access is denied.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $route_name = RouteMatch::createFromRequest($event->getRequest())->getRouteName();
      if ($this->account->isAuthenticated()) {
        switch ($route_name) {
          case 'user.login';
            // Redirect an authenticated user to the profile page.
            $event->setResponse($this->redirect('entity.user.canonical', ['user' => $this->account->id()]));
            break;

          case 'user.register';
            // Redirect an authenticated user to the profile form.
            $event->setResponse($this->redirect('entity.user.edit_form', ['user' => $this->account->id()]));
            break;
        }
      }
      elseif ($route_name === 'user.page') {
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

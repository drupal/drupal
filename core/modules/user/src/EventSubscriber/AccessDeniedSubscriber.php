<?php

namespace Drupal\user\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Redirects users when access is denied.
 *
 * Anonymous users are taken to the login page when attempting to access the
 * user profile pages. Authenticated users are redirected from the login form to
 * their profile page and from the user registration form to their profile edit
 * form.
 */
class AccessDeniedSubscriber extends HttpExceptionSubscriberBase {

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
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats(): array {
    return ['html'];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority(): int {
    // Use a higher priority than ExceptionLoggingSubscriber, because there's
    // no need to log the exception if we can redirect.
    // @see Drupal\Core\EventSubscriber\ExceptionLoggingSubscriber
    return 75;
  }

  /**
   * Redirects users when access is denied.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function on403(ExceptionEvent $event): void {
    $route_name = RouteMatch::createFromRequest($event->getRequest())->getRouteName();
    $redirect_url = NULL;
    if ($this->account->isAuthenticated()) {
      switch ($route_name) {
        case 'user.login':
          // Redirect an authenticated user to the profile page.
          $redirect_url = Url::fromRoute('entity.user.canonical', ['user' => $this->account->id()], ['absolute' => TRUE]);
          break;

        case 'user.register':
          // Redirect an authenticated user to the profile form.
          $redirect_url = Url::fromRoute('entity.user.edit_form', ['user' => $this->account->id()], ['absolute' => TRUE]);
          break;
      }
    }
    elseif ($route_name === 'user.page') {
      $redirect_url = Url::fromRoute('user.login', [], ['absolute' => TRUE]);
    }
    elseif (in_array($route_name, ['user.logout', 'user.logout.confirm'], TRUE)) {
      $redirect_url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
    }

    if ($redirect_url) {
      $event->setResponse(new RedirectResponse($redirect_url->toString()));
    }
  }

}

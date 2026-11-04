<?php

namespace Drupal\Core\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access protection against CSRF attacks.
 */
class CsrfRequestHeaderAccessCheck implements AccessInterface {

  /**
   * A string key that will used to designate the token used by this class.
   */
  const TOKEN_KEY = 'X-CSRF-Token request header';

  /**
   * Constructs a new rest CSRF access check.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $sessionConfiguration
   *   The session configuration.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The token generator.
   */
  public function __construct(
    protected SessionConfigurationInterface $sessionConfiguration,
    protected CsrfTokenGenerator $csrfToken,
  ) {
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request, AccountInterface $account) {
    $method = $request->getMethod();

    // Read-only operations are always allowed.
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS', 'TRACE'], TRUE)) {
      return AccessResult::allowed();
    }

    // This check only applies if
    // 1. the user was successfully authenticated and
    // 2. the request comes with a session cookie.
    if ($account->isAuthenticated()
      && $this->sessionConfiguration->hasSession($request)
    ) {
      if (!$request->headers->has('X-CSRF-Token')) {
        return AccessResult::forbidden()->setReason('X-CSRF-Token request header is missing')->setCacheMaxAge(0);
      }
      $csrf_token = $request->headers->get('X-CSRF-Token');
      if (!$this->csrfToken->validate($csrf_token, self::TOKEN_KEY)) {
        return AccessResult::forbidden()->setReason('X-CSRF-Token request header is invalid')->setCacheMaxAge(0);
      }
    }
    // Let other access checkers decide if the request is legit.
    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}

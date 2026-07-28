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
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs a new rest CSRF access check.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The token generator.
   */
  public function __construct(SessionConfigurationInterface $session_configuration, CsrfTokenGenerator $csrf_token) {
    $this->sessionConfiguration = $session_configuration;
    $this->csrfToken = $csrf_token;
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
        if ($this->csrfToken->validate($csrf_token, 'rest')) {
          @trigger_error("Validating CSRF tokens with the 'rest' key is deprecated in drupal:11.4.0 and is removed from drupal:12.0.0. Sessions created before the upgrade to Drupal 9 are no longer supported. See https://www.drupal.org/node/3591939", E_USER_DEPRECATED);
        }
        else {
          return AccessResult::forbidden()->setReason('X-CSRF-Token request header is invalid')->setCacheMaxAge(0);
        }
      }
    }
    // Let other access checkers decide if the request is legit.
    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}

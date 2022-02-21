<?php

namespace Drupal\Core\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access protection against CSRF attacks.
 */
class CsrfRequestHeaderAccessCheck implements AccessCheckInterface {

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
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    $requirements = $route->getRequirements();
    if (array_key_exists('_csrf_request_header_token', $requirements)) {
      if (isset($requirements['_method'])) {
        // There could be more than one method requirement separated with '|'.
        $methods = explode('|', $requirements['_method']);
        // CSRF protection only applies to write operations, so we can filter
        // out any routes that require reading methods only.
        $write_methods = array_diff($methods, ['GET', 'HEAD', 'OPTIONS', 'TRACE']);
        if (empty($write_methods)) {
          return FALSE;
        }
      }
      // No method requirement given, so we run this access check to be on the
      // safe side.
      return TRUE;
    }
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
      // @todo Remove validate call using 'rest' in 8.3.
      //   Kept here for sessions active during update.
      if (!$this->csrfToken->validate($csrf_token, self::TOKEN_KEY)
        && !$this->csrfToken->validate($csrf_token, 'rest')) {
        return AccessResult::forbidden()->setReason('X-CSRF-Token request header is invalid')->setCacheMaxAge(0);
      }
    }
    // Let other access checkers decide if the request is legit.
    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}

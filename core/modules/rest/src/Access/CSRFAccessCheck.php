<?php

/**
 * @file
 * Contains Drupal\rest\Access\CSRFAccessCheck.
 */

namespace Drupal\rest\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access protection against CSRF attacks.
 */
class CSRFAccessCheck implements AccessCheckInterface {

  /**
   * Implements AccessCheckInterface::applies().
   */
  public function applies(Route $route) {
    $requirements = $route->getRequirements();

    if (array_key_exists('_access_rest_csrf', $requirements)) {
      if (isset($requirements['_method'])) {
        // There could be more than one method requirement separated with '|'.
        $methods = explode('|', $requirements['_method']);
        // CSRF protection only applies to write operations, so we can filter
        // out any routes that require reading methods only.
        $write_methods = array_diff($methods, array('GET', 'HEAD', 'OPTIONS', 'TRACE'));
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
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Request $request, AccountInterface $account) {
    $method = $request->getMethod();
    $cookie = $request->attributes->get('_authentication_provider') == 'cookie';

    // This check only applies if
    // 1. this is a write operation
    // 2. the user was successfully authenticated and
    // 3. the request comes with a session cookie.
    if (!in_array($method, array('GET', 'HEAD', 'OPTIONS', 'TRACE'))
      && $account->isAuthenticated()
      && $cookie
    ) {
      $csrf_token = $request->headers->get('X-CSRF-Token');
      if (!\Drupal::csrfToken()->validate($csrf_token, 'rest')) {
        return static::KILL;
      }
    }
    // Let other access checkers decide if the request is legit.
    return static::ALLOW;
  }
}

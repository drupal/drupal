<?php

/**
 * @file
 * Contains Drupal\rest\Access\CSRFAccessCheck.
 */

namespace Drupal\rest\Access;

use Drupal\Core\Access\AccessCheckInterface;
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
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request) {
    $method = $request->getMethod();
    $cookie = $request->cookies->get(session_name(), FALSE);
    // This check only applies if
    // 1. this is a write operation
    // 2. the user was successfully authenticated and
    // 3. the request comes with a session cookie.
    if (!in_array($method, array('GET', 'HEAD', 'OPTIONS', 'TRACE'))
      && $GLOBALS['user']->isAuthenticated()
      && $cookie
    ) {
      $csrf_token = $request->headers->get('X-CSRF-Token');
      if (!drupal_valid_token($csrf_token, 'rest')) {
        return FALSE;
      }
    }
    // As we do not perform any authorization here we always return NULL to
    // indicate that other access checkers should decide if the request is
    // legit.
    return NULL;
  }
}

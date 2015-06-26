<?php

/**
 * @file
 * Contains \Drupal\rest\Access\CSRFAccessCheck.
 */

namespace Drupal\rest\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access protection against CSRF attacks.
 */
class CSRFAccessCheck implements AccessCheckInterface {

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * Constructs a new rest CSRF access check.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   */
  public function __construct(SessionConfigurationInterface $session_configuration) {
    $this->sessionConfiguration = $session_configuration;
  }

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
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request, AccountInterface $account) {
    $method = $request->getMethod();

    // This check only applies if
    // 1. this is a write operation
    // 2. the user was successfully authenticated and
    // 3. the request comes with a session cookie.
    if (!in_array($method, array('GET', 'HEAD', 'OPTIONS', 'TRACE'))
      && $account->isAuthenticated()
      && $this->sessionConfiguration->hasSession($request)
    ) {
      $csrf_token = $request->headers->get('X-CSRF-Token');
      if (!\Drupal::csrfToken()->validate($csrf_token, 'rest')) {
        return AccessResult::forbidden()->setCacheMaxAge(0);
      }
    }
    // Let other access checkers decide if the request is legit.
    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\rest_test\PageCache\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cache policy for pages requested with REST Test Auth.
 *
 * This policy disallows caching of requests that use the REST Test Auth
 * authentication provider for security reasons (just like basic_auth).
 * Otherwise responses for authenticated requests can get into the page cache
 * and could be delivered to unprivileged users.
 *
 * @see \Drupal\rest_test\Authentication\Provider\TestAuth
 * @see \Drupal\rest_test\Authentication\Provider\TestAuthGlobal
 * @see \Drupal\basic_auth\PageCache\DisallowBasicAuthRequests
 */
class DenyTestAuthRequests implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if ($request->headers->has('REST-test-auth') || $request->headers->has('REST-test-auth-global')) {
      return self::DENY;
    }
  }

}

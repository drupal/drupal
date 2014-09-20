<?php

/**
 * @file
 * Contains \Drupal\Core\PageCache\RequestPolicyInterface.
 */

namespace Drupal\Core\PageCache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the interface for request policy implementations.
 *
 * The request policy is evaluated in order to determine whether delivery of a
 * cached page should be attempted. The caller should do so if static::ALLOW is
 * returned from the check() method.
 */
interface RequestPolicyInterface {

  /**
   * Allow delivery of cached pages.
   */
  const ALLOW = 'allow';

  /**
   * Deny delivery of cached pages.
   */
  const DENY = 'deny';

  /**
   * Determines whether delivery of a cached page should be attempted.
   *
   * Note that the request-policy check runs very early. In particular it is
   * not possible to determine the logged in user. Also the current route match
   * is not yet present when the check runs. Therefore, request-policy checks
   * need to be designed in a way such that they do not depend on any other
   * service and only take in account the information present on the incoming
   * request.
   *
   * When matching against the request path, special attention is needed to
   * support path prefixes which are often used on multilingual sites.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   *
   * @return string|NULL
   *   One of static::ALLOW, static::DENY or NULL. Calling code may attempt to
   *   deliver a cached page if static::ALLOW is returned. Returns NULL if the
   *   policy is not specified for the given request.
   */
  public function check(Request $request);

}

<?php

namespace Drupal\Core\PageCache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the interface for response policy implementations.
 *
 * The response policy is evaluated in order to determine whether a page should
 * be stored in the cache. Calling code should do so unless static::DENY is
 * returned from the check() method.
 */
interface ResponsePolicyInterface {

  /**
   * Deny storage of a page in the cache.
   */
  const DENY = 'deny';

  /**
   * Determines whether it is save to store a page in the cache.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response which is about to be sent to the client.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string|null
   *   Either static::DENY or NULL. Calling code may attempt to store a page in
   *   the cache unless static::DENY is returned. Returns NULL if the policy
   *   policy is not specified for the given response.
   */
  public function check(Response $response, Request $request);

}

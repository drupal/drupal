<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\RequestStackCacheContextBase.
 */

namespace Drupal\Core\Cache\Context;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a base class for cache contexts depending only on the request stack.
 */
abstract class RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new RequestStackCacheContextBase class.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

}

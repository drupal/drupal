<?php

namespace Drupal\Core\Cache\Context;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a base class for cache contexts depending only on the request stack.
 *
 * Subclasses need to implement either
 * \Drupal\Core\Cache\Context\CacheContextInterface or
 * \Drupal\Core\Cache\Context\CalculatedCacheContextInterface.
 */
abstract class RequestStackCacheContextBase {

  /**
   * Constructs a new RequestStackCacheContextBase class.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected RequestStack $requestStack,
  ) {
  }

}

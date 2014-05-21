<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\UrlCacheContext.
 */

namespace Drupal\Core\Cache;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the UrlCacheContext service, for "per page" caching.
 */
class UrlCacheContext implements CacheContextInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Constructs a new UrlCacheContext service.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('URL');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->requestStack->getCurrentRequest()->getUri();
  }

}

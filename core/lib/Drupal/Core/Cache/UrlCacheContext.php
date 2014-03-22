<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\UrlCacheContext.
 */

namespace Drupal\Core\Cache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the UrlCacheContext service, for "per page" caching.
 */
class UrlCacheContext implements CacheContextInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new UrlCacheContext service.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   */
  public function __construct(Request $request) {
    $this->request = $request;
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
    return $this->request->getUri();
  }

}

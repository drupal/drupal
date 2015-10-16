<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\CacheableSecuredRedirectResponse.
 */

namespace Drupal\Core\Routing;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a common base class for cacheable safe redirects.
 */
abstract class CacheableSecuredRedirectResponse extends SecuredRedirectResponse implements CacheableResponseInterface {

  use CacheableResponseTrait;

  /**
   * {@inheritdoc}
   */
  protected function fromResponse(RedirectResponse $response) {
    parent::fromResponse($response);

    $metadata = $this->getCacheableMetadata();
    if ($response instanceof CacheableResponseInterface) {
      $metadata->addCacheableDependency($response->getCacheableMetadata());
    }
    else {
      $metadata->setCacheMaxAge(0);
    }
  }

}

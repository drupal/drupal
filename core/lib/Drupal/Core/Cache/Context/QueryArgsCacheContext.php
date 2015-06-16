<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\QueryArgsCacheContext.
 */

namespace Drupal\Core\Cache\Context;

/**
 * Defines the QueryArgsCacheContext service, for "per query args" caching.
 *
 * A "host" is defined as the combination of URI scheme, domain name and port.
 *
 * @see Symfony\Component\HttpFoundation::getSchemeAndHttpHost()
 */
class QueryArgsCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Query arguments');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($query_arg = NULL) {
    if ($query_arg === NULL) {
      return $this->requestStack->getCurrentRequest()->getQueryString();
    }
    else {
      return $this->requestStack->getCurrentRequest()->query->get($query_arg);
    }
  }

}

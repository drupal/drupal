<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\HeadersCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the HeadersCacheContext service, for "per header" caching.
 */
class HeadersCacheContext extends RequestStackCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('HTTP headers');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($header = NULL) {
    if ($header === NULL) {
      return $this->requestStack->getCurrentRequest()->headers->all();
    }
    else {
      return $this->requestStack->getCurrentRequest()->headers->get($header);
    }
  }

}

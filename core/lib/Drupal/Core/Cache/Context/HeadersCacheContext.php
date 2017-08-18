<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Defines the HeadersCacheContext service, for "per header" caching.
 *
 * Cache context ID: 'headers' (to vary by all headers).
 * Calculated cache context ID: 'headers:%name', e.g. 'headers:X-Something' (to
 * vary by the 'X-Something' header).
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
      $headers = $this->requestStack->getCurrentRequest()->headers->all();
      // Order headers by name to have less cache variations.
      ksort($headers);
      $result = '';
      foreach ($headers as $name => $value) {
        if ($result) {
          $result .= '&';
        }
        // Sort values to minimize cache variations.
        sort($value);
        $result .= $name . '=' . implode(',', $value);
      }
      return $result;
    }
    elseif ($this->requestStack->getCurrentRequest()->headers->has($header)) {
      $value = $this->requestStack->getCurrentRequest()->headers->get($header);
      if ($value !== '') {
        return $value;
      }
      return '?valueless?';
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($header = NULL) {
    return new CacheableMetadata();
  }

}

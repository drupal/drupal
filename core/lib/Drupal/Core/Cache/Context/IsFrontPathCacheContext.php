<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Path\PathMatcherInterface;

/**
 * Defines a cache context for whether the URL is the front page of the site.
 *
 * Cache context ID: 'url.path.is_front'.
 */
class IsFrontPathCacheContext implements CacheContextInterface {

  /**
   * Constructs an IsFrontPathCacheContext object.
   *
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The path matcher.
   */
  public function __construct(
    protected PathMatcherInterface $pathMatcher,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Is front page');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return 'is_front.' . (int) $this->pathMatcher->isFrontPage();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $metadata = new CacheableMetadata();
    $metadata->addCacheTags(['config:system.site']);
    return $metadata;
  }

}

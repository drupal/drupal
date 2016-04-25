<?php

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Provides a test filter to merge with CacheableMetadata.
 *
 * @Filter(
 *   id = "filter_test_cache_merge",
 *   title = @Translation("Testing filter"),
 *   description = @Translation("Does not change content; merges cacheable metadata."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterTestCacheMerge extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    $metadata = new CacheableMetadata();
    $metadata->addCacheTags(['merge:tag']);
    $metadata->addCacheContexts(['user.permissions']);
    $result = $result->merge($metadata);

    return $result;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a test filter to merge with CacheableMetadata.
 */
#[Filter(
  id: "filter_test_cache_merge",
  title: new TranslatableMarkup("Testing filter"),
  description: new TranslatableMarkup("Does not change content; merges cacheable metadata."),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE
)]
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

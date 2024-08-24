<?php

declare(strict_types=1);

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a test filter to associate cache tags.
 */
#[Filter(
  id: "filter_test_cache_tags",
  title: new TranslatableMarkup("Testing filter"),
  description: new TranslatableMarkup("Does not change content; associates cache tags."),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE
)]
class FilterTestCacheTags extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $result->addCacheTags(['foo:bar']);
    $result->addCacheTags(['foo:baz']);
    return $result;
  }

}

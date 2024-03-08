<?php

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\Core\Language\LanguageInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a test filter to associate cache contexts.
 */
#[Filter(
  id: "filter_test_cache_contexts",
  title: new TranslatableMarkup("Testing filter"),
  description: new TranslatableMarkup("Does not change content; associates cache contexts."),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE
)]
class FilterTestCacheContexts extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    // The changes made by this filter are language-specific.
    $result->addCacheContexts(['languages:' . LanguageInterface::TYPE_CONTENT]);
    return $result;
  }

}

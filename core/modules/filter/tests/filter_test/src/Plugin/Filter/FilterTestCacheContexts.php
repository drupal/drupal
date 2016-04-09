<?php

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Core\Language\LanguageInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a test filter to associate cache contexts.
 *
 * @Filter(
 *   id = "filter_test_cache_contexts",
 *   title = @Translation("Testing filter"),
 *   description = @Translation("Does not change content; associates cache contexts."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
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

<?php

/**
 * @file
 * Contains \Drupal\filter_test\Plugin\Filter\FilterTestCacheTags.
 */

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a test filter to associate cache tags
 *
 * @Filter(
 *   id = "filter_test_cache_tags",
 *   title = @Translation("Testing filter"),
 *   description = @Translation("Does not change content; associates cache tags."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterTestCacheTags extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $result->addCacheTags(array('foo:bar'));
    $result->addCacheTags(array('foo:baz'));
    return $result;
  }

}

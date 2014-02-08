<?php

/**
 * @file
 * Contains \Drupal\filter_test\Plugin\Filter\FilterTestUncacheable.
 */

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a test filter that is uncacheable.
 *
 * @Filter(
 *   id = "filter_test_uncacheable",
 *   title = @Translation("Uncacheable filter"),
 *   description = @Translation("Does nothing, but makes a text format uncacheable"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   cache = false
 * )
 */
class FilterTestUncacheable extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    return $text;
  }

}

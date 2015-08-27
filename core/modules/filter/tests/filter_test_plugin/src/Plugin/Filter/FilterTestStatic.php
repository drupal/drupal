<?php

/**
 * @file
 * Contains \Drupal\filter_test_plugin\Plugin\Filter\FilterTestStatic.
 */

namespace Drupal\filter_test_plugin\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter that returns the same static text.
 *
 * @Filter(
 *   id = "filter_static_text",
 *   title = @Translation("Static filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   settings = {},
 * )
 */
class FilterTestStatic extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult('filtered text');
  }

}

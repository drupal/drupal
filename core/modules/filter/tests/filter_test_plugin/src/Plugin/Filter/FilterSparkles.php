<?php

namespace Drupal\filter_test_plugin\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * This filter does not do anything, but enabling of its module is done in a
 * test.
 *
 * @see \Drupal\filter\Tests\FilterFormTest::testFilterForm()
 *
 * @Filter(
 *   id = "filter_sparkles",
 *   title = @Translation("Sparkles filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   settings = {},
 *   weight = -10
 * )
 */
class FilterSparkles extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($text);
  }

}

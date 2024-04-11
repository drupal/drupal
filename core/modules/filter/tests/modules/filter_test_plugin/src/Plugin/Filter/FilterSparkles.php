<?php

namespace Drupal\filter_test_plugin\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * This filter does not do anything, but enabling of its module is done in a
 * test.
 *
 * @see \Drupal\Tests\filter\Functional\FilterFormTest::testFilterForm()
 */
#[Filter(
  id: "filter_sparkles",
  title: new TranslatableMarkup("Sparkles filter"),
  type: FilterInterface::TYPE_HTML_RESTRICTOR,
  weight: -10,
  settings: [],
)]
class FilterSparkles extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($text);
  }

}

<?php

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a test filter to replace all content.
 *
 * @Filter(
 *   id = "filter_test_replace",
 *   title = @Translation("Testing filter"),
 *   description = @Translation("Replaces all content with filter and text format information."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class FilterTestReplace extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = array();
    $text[] = 'Filter: ' . $this->getLabel() . ' (' . $this->getPluginId() . ')';
    $text[] = 'Language: ' . $langcode;
    return new FilterProcessResult(implode("<br />\n", $text));
  }

}

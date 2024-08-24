<?php

declare(strict_types=1);

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a test filter to replace all content.
 */
#[Filter(
  id: "filter_test_replace",
  title: new TranslatableMarkup("Testing filter"),
  description: new TranslatableMarkup("Replaces all content with filter and text format information."),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
)]
class FilterTestReplace extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = [];
    $text[] = 'Filter: ' . $this->getLabel() . ' (' . $this->getPluginId() . ')';
    $text[] = 'Language: ' . $langcode;
    return new FilterProcessResult(implode("<br />\n", $text));
  }

}

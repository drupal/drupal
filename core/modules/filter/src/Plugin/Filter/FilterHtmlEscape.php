<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter to display any HTML as plain text.
 */
#[Filter(
  id: "filter_html_escape",
  title: new TranslatableMarkup("Display any HTML as plain text"),
  type: FilterInterface::TYPE_HTML_RESTRICTOR,
  weight: -10
)]
class FilterHtmlEscape extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_filter_html_escape($text));
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    // Nothing is allowed.
    return ['allowed' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('No HTML tags allowed.');
  }

}

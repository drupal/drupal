<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter to convert line breaks to HTML.
 */
#[Filter(
  id: "filter_autop",
  title: new TranslatableMarkup("Convert line breaks into HTML (i.e. <code>&lt;br&gt;</code> and <code>&lt;p&gt;</code>)"),
  type: FilterInterface::TYPE_MARKUP_LANGUAGE
)]
class FilterAutoP extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_filter_autop($text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Lines and paragraphs are automatically recognized. The &lt;br /&gt; line break, &lt;p&gt; paragraph and &lt;/p&gt; close paragraph tags are inserted automatically. If paragraphs are not recognized simply add a couple of blank lines.');
    }
    else {
      return $this->t('Lines and paragraphs break automatically.');
    }
  }

}

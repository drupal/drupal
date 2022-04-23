<?php

namespace Drupal\ckeditor5_incompatible_filter_test\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter incompatible with CKEditor 5.
 *
 * @Filter(
 *   id = "filter_incompatible",
 *   title = @Translation("A TYPE_MARKUP_LANGUAGE filter incompatible with CKEditor 5"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class FilterIsIncompatible extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($text);
  }

}

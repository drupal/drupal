<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_incompatible_filter_test\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter incompatible with CKEditor 5.
 */
#[Filter(
  id: "filter_incompatible",
  title: new TranslatableMarkup("A TYPE_MARKUP_LANGUAGE filter incompatible with CKEditor 5"),
  type: FilterInterface::TYPE_MARKUP_LANGUAGE
)]
class FilterIsIncompatible extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($text);
  }

}

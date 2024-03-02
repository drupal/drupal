<?php

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'number_unformatted' formatter.
 */
#[FieldFormatter(
  id: 'number_unformatted',
  label: new TranslatableMarkup('Unformatted'),
  field_types: [
    'integer',
    'decimal',
    'float',
  ],
)]
class NumericUnformattedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $item->value];
    }

    return $elements;
  }

}

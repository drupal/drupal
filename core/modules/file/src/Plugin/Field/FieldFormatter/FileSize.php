<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formatter that shows the file byte size in a human-readable way.
 */
#[FieldFormatter(
  id: 'file_size',
  label: new TranslatableMarkup('Bytes (KB, MB, ...)'),
  field_types: [
    'integer',
  ],
)]
class FileSize extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => ByteSizeMarkup::create((int) $item->value)];
    }

    return $elements;
  }

}

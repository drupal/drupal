<?php

namespace Drupal\field_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'field_no_settings' formatter.
 */
#[FieldFormatter(
  id: 'field_no_settings',
  label: new TranslatableMarkup('Field no settings'),
  field_types: [
    'test_field',
  ],
  weight: -10,
)]
class TestFieldNoSettingsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // This formatter only needs to output raw for testing.
      $elements[$delta] = ['#markup' => $item->value];
    }

    return $elements;
  }

}

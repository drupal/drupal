<?php

/**
 * @file
 *
 * Contains \Drupal\field_test\Plugin\field\formatter\TestFieldEmptyFormatter.
 */
namespace Drupal\field_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'field_empty_test' formatter.
 *
 * @FieldFormatter(
 *   id = "field_empty_test",
 *   label = @Translation("Field empty test"),
 *   field_types = {
 *     "test_field",
 *   },
 *   weight = -5
 * )
 */
class TestFieldEmptyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'test_empty_string' => '**EMPTY FIELD**',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    if ($items->isEmpty()) {
      // For fields with no value, just add the configured "empty" value.
      $elements[0] = array('#markup' => $this->getSetting('test_empty_string'));
    }
    else {
      foreach ($items as $delta => $item) {
        // This formatter only needs to output raw for testing.
        $elements[$delta] = array('#markup' => $item->value);
      }
    }

    return $elements;
  }

}

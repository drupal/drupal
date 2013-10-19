<?php

/**
 * @file
 * Contains \Drupal\number\Plugin\field\formatter\NumberUnformattedFormatter.
 */

namespace Drupal\number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'number_unformatted' formatter.
 *
 * @FieldFormatter(
 *   id = "number_unformatted",
 *   label = @Translation("Unformatted"),
 *   field_types = {
 *     "number_integer",
 *     "number_decimal",
 *     "number_float"
 *   }
 * )
 */
class NumberUnformattedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $item->value);
    }

    return $elements;
  }

}

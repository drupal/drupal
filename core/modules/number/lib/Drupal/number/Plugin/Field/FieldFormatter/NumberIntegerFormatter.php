<?php

/**
 * @file
 * Contains \Drupal\number\Plugin\field\formatter\NumberIntegerFormatter.
 */

namespace Drupal\number\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'number_integer' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "number_integer",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "number_integer"
 *   },
 *   settings = {
 *     "thousand_separator" = "",
 *     "prefix_suffix" = "TRUE"
 *   }
 * )
 */
class NumberIntegerFormatter extends DefaultNumberFormatter {

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    return number_format($number, 0, '', $this->getSetting('thousand_separator'));
  }

}

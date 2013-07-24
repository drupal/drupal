<?php

/**
 * @file
 * Definition of Drupal\number\Plugin\field\formatter\NumberIntegerFormatter.
 */

namespace Drupal\number\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\number\Plugin\field\formatter\DefaultNumberFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Plugin implementation of the 'number_integer' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "number_integer",
 *   module = "number",
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
   * Overrides Drupal\number\Plugin\field\formatter\DefaultNumberFormatter::numberFormat().
   */
  protected function numberFormat($number) {
    return number_format($number, 0, '', $this->getSetting('thousand_separator'));
  }

}

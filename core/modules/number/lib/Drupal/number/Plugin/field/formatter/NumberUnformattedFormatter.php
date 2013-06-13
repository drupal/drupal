<?php

/**
 * @file
 * Definition of Drupal\number\Plugin\field\formatter\NumberUnformattedFormatter.
 */

namespace Drupal\number\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'number_unformatted' formatter.
 *
 * @FieldFormatter(
 *   id = "number_unformatted",
 *   module = "number",
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
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $item['value']);
    }

    return $elements;
  }

}

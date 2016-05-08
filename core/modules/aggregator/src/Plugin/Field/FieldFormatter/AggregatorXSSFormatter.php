<?php

namespace Drupal\aggregator\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'aggregator_xss' formatter.
 *
 * @FieldFormatter(
 *   id = "aggregator_xss",
 *   label = @Translation("Aggregator XSS"),
 *   description = @Translation("Filter output for aggregator items"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class AggregatorXSSFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->value,
        '#allowed_tags' => _aggregator_allowed_tags(),
      ];
    }
    return $elements;
  }

}

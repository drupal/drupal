<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\field\formatter\OptionsKeyFormatter.
 */

namespace Drupal\options\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'list_key' formatter.
 *
 * @FieldFormatter(
 *   id = "list_key",
 *   label = @Translation("Key"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_text",
 *   }
 * )
 */
class OptionsKeyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => field_filter_xss($item->value));
    }

    return $elements;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\formatter\TextPlainFormatter.
 */

namespace Drupal\text\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "text_plain",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   edit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class TextPlainFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      // The text value has no text format assigned to it, so the user input
      // should equal the output, including newlines.
      $elements[$delta] = array('#markup' => nl2br(check_plain($item->value)));
    }

    return $elements;
  }

}

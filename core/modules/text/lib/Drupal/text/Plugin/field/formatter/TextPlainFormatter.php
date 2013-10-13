<?php

/**
 * @file
 * Definition of Drupal\text\Plugin\field\formatter\TextPlainFormatter.
 */

namespace Drupal\text\Plugin\field\formatter;

use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\Field\FieldItemListInterface;

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
 *     "editor" = "direct"
 *   }
 * )
 */
class TextPlainFormatter extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
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

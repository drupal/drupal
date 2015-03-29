<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\BasicStringFormatter.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'basic_string' formatter.
 *
 * @FieldFormatter(
 *   id = "basic_string",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "string_long",
 *     "email"
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class BasicStringFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      // The text value has no text format assigned to it, so the user input
      // should equal the output, including newlines.
      $elements[$delta] = array('#markup' => nl2br(SafeMarkup::checkPlain($item->value)));
    }

    return $elements;
  }

}

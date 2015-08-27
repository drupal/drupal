<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\Field\FieldFormatter\OptionsKeyFormatter.
 */

namespace Drupal\options\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldFilteredString;
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
 *     "list_string",
 *   }
 * )
 */
class OptionsKeyFormatter extends FormatterBase {

  use AllowedTagsXssTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#markup' => $item->value,
        '#allowed_tags' => FieldFilteredString::allowedTags(),
      );
    }

    return $elements;
  }

}

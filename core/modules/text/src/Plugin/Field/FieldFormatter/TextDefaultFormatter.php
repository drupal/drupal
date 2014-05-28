<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\formatter\TextDefaultFormatter.
 */

namespace Drupal\text\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "text_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class TextDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#markup' => $item->processed, 
        '#cache' => array(
          'tags' => array(
            'filter_format' => array(
              $item->format,
            ),
          ),
        ),
      );
    }

    return $elements;
  }

}

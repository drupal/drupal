<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\UriLinkFormatter.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'uri_link' formatter.
 *
 * @FieldFormatter(
 *   id = "uri_link",
 *   label = @Translation("Link to URI"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class UriLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'link',
        '#href' => $item->value,
        '#title' => $item->value,
      );
    }

    return $elements;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\PlainFormatter.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'taxonomy_term_reference_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "taxonomy_term_reference_plain",
 *   label = @Translation("Plain text"),
 *   field_types = {
 *     "taxonomy_term_reference"
 *   }
 * )
 */
class PlainFormatter extends TaxonomyFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#markup' => check_plain($item->entity->label()),
      );
    }

    return $elements;
  }

}

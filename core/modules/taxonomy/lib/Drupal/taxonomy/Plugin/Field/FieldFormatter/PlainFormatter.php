<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\PlainFormatter.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\String;

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
        '#markup' => String::checkPlain($item->entity->label()),
      );
    }

    return $elements;
  }

}

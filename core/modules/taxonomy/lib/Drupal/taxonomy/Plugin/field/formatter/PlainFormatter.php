<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\PlainFormatter.
 */

namespace Drupal\taxonomy\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\taxonomy\Plugin\field\formatter\TaxonomyFormatterBase;

/**
 * Plugin implementation of the 'taxonomy_term_reference_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "taxonomy_term_reference_plain",
 *   module = "taxonomy",
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
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#markup' => check_plain($item['entity']->label()),
      );
    }

    return $elements;
  }

}

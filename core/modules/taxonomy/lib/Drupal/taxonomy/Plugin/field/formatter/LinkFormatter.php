<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\LinkFormatter.
 */

namespace Drupal\taxonomy\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\taxonomy\Plugin\field\formatter\TaxonomyFormatterBase;

/**
 * Plugin implementation of the 'taxonomy_term_reference_link' formatter.
 *
 * @FieldFormatter(
 *   id = "taxonomy_term_reference_link",
 *   module = "taxonomy",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "taxonomy_term_reference"
 *   }
 * )
 */
class LinkFormatter extends TaxonomyFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    // Terms without target_id do not exist yet, theme such terms as just their
    // name.
    foreach ($items as $delta => $item) {
      if (!$item['target_id']) {
        $elements[$delta] = array(
          '#markup' => check_plain($item['entity']->label()),
        );
      }
      else {
        $term = $item['entity'];
        $uri = $term->uri();
        $elements[$delta] = array(
          '#type' => 'link',
          '#title' => $term->label(),
          '#href' => $uri['path'],
          '#options' => $uri['options'],
        );
      }
    }

    return $elements;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\LinkFormatter.
 */

namespace Drupal\taxonomy\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\taxonomy\Plugin\field\formatter\TaxonomyFormatterBase;

/**
 * Plugin implementation of the 'taxonomy_term_reference_link' formatter.
 *
 * @Plugin(
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

    // Terms whose tid is 'autocreate' do not exist yet and $item['entity'] is
    // not set. Theme such terms as just their name.
    foreach ($items as $delta => $item) {
      if ($item['tid'] == 'autocreate') {
        $elements[$delta] = array(
          '#markup' => check_plain($item['name']),
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

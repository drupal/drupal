<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\PlainFormatter.
 */

namespace Drupal\taxonomy\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\taxonomy\Plugin\field\formatter\TaxonomyFormatterBase;

/**
 * Plugin implementation of the 'taxonomy_term_reference_plain' formatter.
 *
 * @Plugin(
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

    // Terms whose tid is 'autocreate' do not exist yet and $item['entity'] is
    // not set. Theme such terms as just their name.
    foreach ($items as $delta => $item) {
      $name = ($item['tid'] != 'autocreate' ? $item['entity']->label() : $item['name']);
      $elements[$delta] = array(
        '#markup' => check_plain($name),
      );
    }

    return $elements;
  }

}

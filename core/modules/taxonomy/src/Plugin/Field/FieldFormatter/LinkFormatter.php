<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\LinkFormatter.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\String;

/**
 * Plugin implementation of the 'taxonomy_term_reference_link' formatter.
 *
 * @FieldFormatter(
 *   id = "taxonomy_term_reference_link",
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
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    // Terms without target_id do not exist yet, theme such terms as just their
    // name.
    foreach ($items as $delta => $item) {
      if (!$item->target_id) {
        $elements[$delta] = array(
          '#markup' => String::checkPlain($item->entity->label()),
        );
      }
      else {
        /** @var $term \Drupal\taxonomy\TermInterface */
        $term = $item->entity;
        $elements[$delta] = array(
          '#type' => 'link',
          '#title' => $term->getName(),
          '#url' => $term->urlInfo(),
        );

        if (!empty($item->_attributes)) {
          $options = $elements[$delta]['#url']->getOptions();
          $options += ['attributes' => []];
          $options['attributes'] += $item->_attributes;
          $elements[$delta]['#url']->setOptions($options);
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }

        $elements[$delta]['#cache']['tags'] = $item->entity->getCacheTag();
      }
    }

    return $elements;
  }

}

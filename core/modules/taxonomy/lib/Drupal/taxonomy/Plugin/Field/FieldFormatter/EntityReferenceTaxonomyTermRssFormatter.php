<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\EntityReferenceTaxonomyTermRssFormatter.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_reference\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference taxonomy term RSS' formatter.
 *
 * @todo: Have a way to indicate this formatter applies only to taxonomy terms.
 *
 * @FieldFormatter(
 *   id = "entity_reference_rss_category",
 *   label = @Translation("RSS category"),
 *   description = @Translation("Display reference to taxonomy term in RSS."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceTaxonomyTermRssFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $entity = $items->getEntity();

    foreach ($items as $item) {
      $entity->rss_elements[] = array(
        'key' => 'category',
        'value' => $item->entity->label(),
        'attributes' => array(
          'domain' => $item->target_id ? url('taxonomy/term/' . $item->target_id, array('absolute' => TRUE)) : '',
        ),
      );
    }

    return $elements;
  }
}

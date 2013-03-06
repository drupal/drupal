<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\field\formatter\EntityReferenceIdFormatter.
 */

namespace Drupal\entity_reference\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference ID' formatter.
 *
 * @Plugin(
 *   id = "entity_reference_entity_id",
 *   module = "entity_reference",
 *   label = @Translation("Entity ID"),
 *   description = @Translation("Display the ID of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceIdFormatter extends EntityReferenceFormatterBase {

  /**
   * Overrides \Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (!empty($item['entity'])) {
        $elements[$delta] = array('#markup' => check_plain($item['target_id']));
      }
    }

    return $elements;
  }
}

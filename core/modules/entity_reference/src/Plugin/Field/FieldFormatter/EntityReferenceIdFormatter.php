<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldFormatter\EntityReferenceIdFormatter.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\String;

/**
 * Plugin implementation of the 'entity reference ID' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_id",
 *   label = @Translation("Entity ID"),
 *   description = @Translation("Display the ID of the referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceIdFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (!$item->access) {
        // User doesn't have access to the referenced entity.
        continue;
      }
      if (!empty($item->entity) && !empty($item->target_id)) {
        /** @var $referenced_entity \Drupal\Core\Entity\EntityInterface */
        $referenced_entity = $item->entity;
        $elements[$delta] = array(
          '#markup' => String::checkPlain($item->target_id),
          // Create a cache tag entry for the referenced entity. In the case
          // that the referenced entity is deleted, the cache for referring
          // entities must be cleared.
          '#cache' => array(
            'tags' => $referenced_entity->getCacheTag(),
          ),
        );
      }
    }

    return $elements;
  }
}

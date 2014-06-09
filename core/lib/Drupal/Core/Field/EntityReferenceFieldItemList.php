<?php

/**
 * @file
 * Contains \Drupal\Core\Field\EntityReferenceFieldItemList.
 */

namespace Drupal\Core\Field;

/**
 * Defines a item list class for entity reference fields.
 */
class EntityReferenceFieldItemList extends FieldItemList implements EntityReferenceFieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if (empty($this->list)) {
      return array();
    }

    // Get a list of items having non-empty target ids.
    $list = array_filter($this->list, function($item) {
      return (bool) $item->target_id;
    });

    $ids = array();
    foreach ($list as $delta => $item) {
      $ids[$delta] = $item->target_id;
    }
    if (empty($ids)) {
      return array();
    }

    $target_type = $this->getFieldDefinition()->getSetting('target_type');
    $entities = \Drupal::entityManager()->getStorage($target_type)->loadMultiple($ids);

    $target_entities = array();
    foreach ($ids as $delta => $target_id) {
      if (isset($entities[$target_id])) {
        $target_entities[$delta] = $entities[$target_id];
      }
    }

    return $target_entities;
  }

}

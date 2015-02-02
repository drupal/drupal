<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Parent plugin for entity reference formatters.
 */
abstract class EntityReferenceFormatterBase extends FormatterBase {

  /**
   * Returns the accessible and translated entities for view.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The item list.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The entities to view.
   */
  protected function getEntitiesToView(FieldItemListInterface $items) {
    $entities = array();

    $parent_entity_langcode = $items->getEntity()->language()->getId();
    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        $entity = $item->entity;

        // Set the entity in the correct language for display.
        if ($entity instanceof TranslatableInterface && $entity->hasTranslation($parent_entity_langcode)) {
          $entity = $entity->getTranslation($parent_entity_langcode);
        }

        // Check entity access.
        if ($entity->access('view')) {
          $entities[$delta] = $entity;
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed.
   */
  public function prepareView(array $entities_items) {
    // Load the existing (non-autocreate) entities. For performance, we want to
    // use a single "multiple entity load" to load all the entities for the
    // multiple "entity reference item lists" that are being displayed. We thus
    // cannot use
    // \Drupal\Core\Field\EntityReferenceFieldItemList::referencedEntities().
    $ids = array();
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        // To avoid trying to reload non-existent entities in
        // getEntitiesToView(), explicitly mark the items where $item->entity
        // contains a valid entity ready for display. All items are initialized
        // at FALSE.
        $item->_loaded = FALSE;
        if ($item->target_id !== NULL) {
          $ids[] = $item->target_id;
        }
      }
    }
    if ($ids) {
      $target_type = $this->getFieldSetting('target_type');
      $target_entities = \Drupal::entityManager()->getStorage($target_type)->loadMultiple($ids);
    }

    // For each item, pre-populate the loaded entity in $item->entity, and set
    // the 'loaded' flag.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if (isset($target_entities[$item->target_id])) {
          $item->entity = $target_entities[$item->target_id];
          $item->_loaded = TRUE;
        }
        elseif ($item->hasNewEntity()) {
          $item->_loaded = TRUE;
        }
      }
    }
  }

}

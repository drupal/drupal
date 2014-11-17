<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase.
 */

namespace Drupal\entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Field\FieldItemListInterface;

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
      // The "originalEntity" property is assigned in self::prepareView() and
      // its absence means that the referenced entity was neither found in the
      // persistent storage nor is it a new entity (e.g. from "autocreate").
      if (!isset($item->originalEntity)) {
        $item->access = FALSE;
        continue;
      }

      if ($item->originalEntity instanceof TranslatableInterface && $item->originalEntity->hasTranslation($parent_entity_langcode)) {
        $entity = $item->originalEntity->getTranslation($parent_entity_langcode);
      }
      else {
        $entity = $item->originalEntity;
      }

      if ($item->access || $entity->access('view')) {
        $entities[$delta] = $entity;

        // Mark item as accessible.
        $item->access = TRUE;
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed, and places them in a custom item property for getEntitiesToView().
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
        if ($item->target_id !== NULL) {
          $ids[] = $item->target_id;
        }
      }
    }
    if ($ids) {
      $target_type = $this->getFieldSetting('target_type');
      $target_entities = \Drupal::entityManager()->getStorage($target_type)->loadMultiple($ids);
    }

    // For each item, place the referenced entity where getEntitiesToView()
    // reads it.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if (isset($target_entities[$item->target_id])) {
          $item->originalEntity = $target_entities[$item->target_id];
        }
        elseif ($item->hasNewEntity()) {
          $item->originalEntity = $item->entity;
        }
      }
    }
  }

}

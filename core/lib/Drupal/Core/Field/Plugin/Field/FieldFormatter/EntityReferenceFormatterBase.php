<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Parent plugin for entity reference formatters.
 */
abstract class EntityReferenceFormatterBase extends FormatterBase {

  /**
   * Returns the referenced entities for display.
   *
   * The method takes care of:
   * - checking entity access,
   * - placing the entities in the language expected for display.
   * It is thus strongly recommended that formatters use it in their
   * implementation of viewElements($items) rather than dealing with $items
   * directly.
   *
   * For each entity, the EntityReferenceItem by which the entity is referenced
   * is available in $entity->_referringItem. This is useful for field types
   * that store additional values next to the reference itself.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of referenced entities to display, keyed by delta.
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items) {
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

        // Check entity access if needed.
        if (!$this->needsAccessCheck($item) || $entity->access('view')) {
          // Add the referring item, in case the formatter needs it.
          $entity->_referringItem = $items[$delta];
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
    // Collect entity IDs to load. For performance, we want to use a single
    // "multiple entity load" to load all the entities for the multiple
    // "entity reference item lists" being displayed. We thus cannot use
    // \Drupal\Core\Field\EntityReferenceFieldItemList::referencedEntities().
    $ids = array();
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        // To avoid trying to reload non-existent entities in
        // getEntitiesToView(), explicitly mark the items where $item->entity
        // contains a valid entity ready for display. All items are initialized
        // at FALSE.
        $item->_loaded = FALSE;
        if ($this->needsEntityLoad($item)) {
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

  /**
   * Returns whether the entity referenced by an item needs to be loaded.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *    The item to check.
   *
   * @return bool
   *   TRUE if the entity needs to be loaded.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * Returns whether entity access should be checked.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *    The item to check.
   *
   * @return bool
   *   TRUE if entity access should be checked.
   */
  protected function needsAccessCheck(EntityReferenceItem $item) {
    return TRUE;
  }

}

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
   * Mark the accessible IDs a user can see. We do not unset unaccessible
   * values, as other may want to act on those values, even if they can
   * not be accessed.
   */
  public function prepareView(array $entities_items) {
    $target_ids = array();
    $revision_ids = array();

    // Collect every possible entity attached to any of the entities.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if (!empty($item->revision_id)) {
          $revision_ids[] = $item->revision_id;
        }
        elseif (!empty($item->target_id)) {
          $target_ids[] = $item->target_id;
        }
      }
    }

    $target_type = $this->getFieldSetting('target_type');

    $target_entities = array();

    if ($target_ids) {
      $target_entities = entity_load_multiple($target_type, $target_ids);
    }

    if ($revision_ids) {
      // We need to load the revisions one by-one.
      foreach ($revision_ids as $revision_id) {
        $target_entity = entity_revision_load($target_type, $revision_id);
        // Use the revision ID in the key.
        $identifier = $target_entity->id() . ':' . $revision_id;
        $target_entities[$identifier] = $target_entity;
      }
    }

    // Iterate through the fieldable entities again to attach the loaded data.
    foreach ($entities_items as $items) {
      $rekey = FALSE;
      foreach ($items as $item) {
        // If we have a revision ID, the key uses it as well.
        $identifier = !empty($item->revision_id) ? $item->target_id . ':' . $item->revision_id : $item->target_id;
        if ($item->target_id !== 0) {
          if (!isset($target_entities[$identifier])) {
            // The entity no longer exists, so empty the item.
            $item->setValue(NULL);
            $rekey = TRUE;
            continue;
          }
        }
        else {
          // This is an "auto_create" item, just leave the entity in place.
        }

        $item->originalEntity = $target_entities[$identifier];
      }

      // Rekey the items array if needed.
      if ($rekey) {
        $items->filterEmptyItems();
      }
    }
  }

}

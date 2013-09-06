<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase.
 */

namespace Drupal\entity_reference\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;

/**
 * Parent plugin for entity reference formatters.
 */
abstract class EntityReferenceFormatterBase extends FormatterBase {

  /**
   * Overrides \Drupal\field\Plugin\Type\Formatter\FormatterBase::prepareView().
   *
   * Mark the accessible IDs a user can see. We do not unset unaccessible
   * values, as other may want to act on those values, even if they can
   * not be accessed.
   */
  public function prepareView(array $entities, $langcode, array $items) {
    $target_ids = array();
    $revision_ids = array();

    // Collect every possible entity attached to any of the entities.
    foreach ($entities as $id => $entity) {
      foreach ($items[$id] as $item) {
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
        $entity = entity_revision_load($target_type, $revision_id);
        // Use the revision ID in the key.
        $identifier = $entity->id() . ':' . $revision_id;
        $target_entities[$identifier] = $entity;
      }
    }

    // Iterate through the fieldable entities again to attach the loaded data.
    foreach ($entities as $id => $entity) {
      $rekey = FALSE;
      foreach ($items[$id] as $delta => $item) {
        // If we have a revision ID, the key uses it as well.
        $identifier = !empty($item->revision_id) ? $item->target_id . ':' . $item->revision_id : $item->target_id;
        if ($item->target_id !== 0) {
          if (!isset($target_entities[$identifier])) {
            // The entity no longer exists, so remove the key.
            $rekey = TRUE;
            unset($items[$id][$delta]);
            continue;
          }

          $entity = $target_entities[$identifier];
          $item->entity = $entity;

          if (!$entity->access('view')) {
            continue;
          }
        }
        else {
          // This is an "auto_create" item, just leave the entity in place.
        }

        // Mark item as accessible.
        $item->access = TRUE;
      }

      if ($rekey) {
        // Rekey the items array.
        $items[$id] = array_values($items[$id]);
      }
    }
  }

  /**
   * Overrides \Drupal\field\Plugin\Type\Formatter\FormatterBase::viewElements().
   *
   * @see \Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, FieldInterface $items) {
    // Remove un-accessible items.
    foreach ($items as $delta => $item) {
      if (empty($item->access)) {
        unset($items[$delta]);
      }
    }
    return array();
  }
}

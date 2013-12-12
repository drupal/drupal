<?php

/**
 * @file
 * Contains \Drupal\Core\Field\ConfigEntityReferenceItemBase.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * A common base class for configurable entity reference fields.
 *
 * Extends the Core 'entity_reference' entity field item with common methods
 * used in general configurable entity reference field.
 */
class ConfigEntityReferenceItemBase extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Avoid loading the entity by first checking the 'target_id'.
    $target_id = $this->target_id;
    if (!empty($target_id)) {
      return FALSE;
    }
    // Allow auto-create entities.
    if (empty($target_id) && ($entity = $this->get('entity')->getValue()) && $entity->isNew()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $entity = $this->get('entity')->getValue();
    $target_id = $this->get('target_id')->getValue();

    if (!$target_id && !empty($entity) && $entity->isNew()) {
      $entity->save();
      $this->set('target_id', $entity->id());
    }
  }

}

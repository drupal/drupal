<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\field_type\FileField.
 */

namespace Drupal\file\Plugin\field\field_type;

use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemList;

/**
 * Represents a configurable entity file field.
 */
class FileFieldItemList extends ConfigFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, array &$form_state) { }

  /**
   * {@inheritdoc}
   */
  public function update() {
    parent::update();
    $this->updateFileUsage();
  }

  /**
   * Updates the file usage.
   */
  protected function updateFileUsage() {
    $entity = $this->getRoot();

    // On new revisions, all files are considered to be a new usage and no
    // deletion of previous file usages are necessary.
    if (!empty($entity->original) && $entity->getRevisionId() != $entity->original->getRevisionId()) {
      foreach ($this->list as $item) {
        file_usage()->add($item->entity, 'file', $entity->entityType(), $entity->id());
      }
      return;
    }

    // Build a list of the current target IDs.
    $fids = array();
    foreach ($this->list as $item) {
      $fids[] = $item->target_id;
    }

    // Compare the original field values with the ones that are being saved.
    $field_name = $this->getFieldDefinition()->getFieldName();
    $original_fids = array();
    $original_items = $entity->original->getTranslation($this->getLangcode())->$field_name;
    foreach ($original_items as $item) {
      $original_fids[] = $item->target_id;
      if ($item->target_id && !in_array($item->target_id, $fids)) {
        // Decrement the file usage count by 1.
        file_usage()->delete($item->entity, 'file', $entity->entityType(), $entity->id());
      }
    }

    // Add new usage entries for newly added files.
    foreach ($this->list as $item) {
      if ($item->target_id && !in_array($item->target_id, $original_fids)) {
        file_usage()->add($item->entity, 'file', $entity->entityType(), $entity->id());
      }
    }
  }

}

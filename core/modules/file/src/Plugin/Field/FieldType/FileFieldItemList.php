<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldType\FileFieldItemList.
 */

namespace Drupal\file\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents a configurable entity file field.
 */
class FileFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function insert() {
    parent::insert();
    $entity = $this->getEntity();

    // Add a new usage for newly uploaded files.
    foreach ($this->referencedEntities() as $file) {
      \Drupal::service('file.usage')->add($file, 'file', $entity->getEntityTypeId(), $entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    parent::update();
    $entity = $this->getEntity();

    // Get current target file entities and file IDs.
    $files = $this->referencedEntities();
    $fids = array();

    foreach ($files as $file) {
      $fids[] = $file->id();
    }

    // On new revisions, all files are considered to be a new usage and no
    // deletion of previous file usages are necessary.
    if (!empty($entity->original) && $entity->getRevisionId() != $entity->original->getRevisionId()) {
      foreach ($files as $file) {
        \Drupal::service('file.usage')->add($file, 'file', $entity->getEntityTypeId(), $entity->id());
      }
      return;
    }

    // Get the file IDs attached to the field before this update.
    $field_name = $this->getFieldDefinition()->getName();
    $original_fids = array();
    $original_items = $entity->original->getTranslation($this->getLangcode())->$field_name;
    foreach ($original_items as $item) {
      $original_fids[] = $item->target_id;
    }

    // Decrement file usage by 1 for files that were removed from the field.
    $removed_fids = array_filter(array_diff($original_fids, $fids));
    $removed_files = \Drupal::entityManager()->getStorage('file')->loadMultiple($removed_fids);
    foreach ($removed_files as $file) {
      \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id());
    }

    // Add new usage entries for newly added files.
    foreach ($files as $file) {
      if (!in_array($file->id(), $original_fids)) {
        \Drupal::service('file.usage')->add($file, 'file', $entity->getEntityTypeId(), $entity->id());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    $entity = $this->getEntity();

    // Delete all file usages within this entity.
    foreach ($this->referencedEntities() as $file) {
      \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id(), 0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    parent::deleteRevision();
    $entity = $this->getEntity();

    // Decrement the file usage by 1.
    foreach ($this->referencedEntities() as $file) {
      \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id());
    }
  }

}

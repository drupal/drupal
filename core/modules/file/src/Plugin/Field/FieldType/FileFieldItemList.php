<?php

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
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $entity = $this->getEntity();

    if (!$update) {
      // Add a new usage for newly uploaded files.
      foreach ($this->referencedEntities() as $file) {
        \Drupal::service('file.usage')->add($file, 'file', $entity->getEntityTypeId(), $entity->id());
      }
    }
    else {
      // Get current target file entities and file IDs.
      $files = $this->referencedEntities();
      $ids = [];

      /** @var \Drupal\file\FileInterface $file */
      foreach ($files as $file) {
        $ids[] = $file->id();
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
      $original_ids = [];
      $langcode = $this->getLangcode();
      $original = $entity->original;
      if ($original->hasTranslation($langcode)) {
        $original_items = $original->getTranslation($langcode)->{$field_name};
        foreach ($original_items as $item) {
          $original_ids[] = $item->target_id;
        }
      }

      // Decrement file usage by 1 for files that were removed from the field.
      $removed_ids = array_filter(array_diff($original_ids, $ids));
      $removed_files = \Drupal::entityManager()->getStorage('file')->loadMultiple($removed_ids);
      foreach ($removed_files as $file) {
        \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id());
      }

      // Add new usage entries for newly added files.
      foreach ($files as $file) {
        if (!in_array($file->id(), $original_ids)) {
          \Drupal::service('file.usage')->add($file, 'file', $entity->getEntityTypeId(), $entity->id());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    $entity = $this->getEntity();

    // If a translation is deleted only decrement the file usage by one. If the
    // default translation is deleted remove all file usages within this entity.
    $count = $entity->isDefaultTranslation() ? 0 : 1;
    foreach ($this->referencedEntities() as $file) {
      \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id(), $count);
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

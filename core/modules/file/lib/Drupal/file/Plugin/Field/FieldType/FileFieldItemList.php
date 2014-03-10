<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldType\FileFieldItemList.
 */

namespace Drupal\file\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

/**
 * Represents a configurable entity file field.
 */
class FileFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, array &$form_state) { }

  /**
   * {@inheritdoc}
   */
  public function insert() {
    parent::insert();
    $entity = $this->getEntity();

    // Add a new usage for newly uploaded files.
    foreach ($this->targetEntities() as $file) {
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
    $files = $this->targetEntities();
    $fids = array_keys($files);

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
    $removed_files = \Drupal::entityManager()->getStorageController('file')->loadMultiple($removed_fids);
    foreach ($removed_files as $file) {
      \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id());
    }

    // Add new usage entries for newly added files.
    foreach ($files as $fid => $file) {
      if (!in_array($fid, $original_fids)) {
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
    foreach ($this->targetEntities() as $file) {
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
    foreach ($this->targetEntities() as $file) {
      \Drupal::service('file.usage')->delete($file, 'file', $entity->getEntityTypeId(), $entity->id());
    }
  }

  /**
   * Collects target file entities for this field.
   *
   * @return array
   *   An array with the list of target file entities keyed by file ID.
   *
   * @todo Drop this when https://drupal.org/node/2073661 lands.
   */
  protected function targetEntities() {
    if (!isset($this->list)) {
      return array();
    }
    $ids = array();
    foreach ($this->list as $item) {
      $ids[] = $item->target_id;
    }
    // Prevent NULLs as target IDs.
    $ids = array_filter($ids);

    return $ids ? \Drupal::entityManager()->getStorageController('file')->loadMultiple($ids) : array();
  }

}

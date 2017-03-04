<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines a null entity storage.
 *
 * Used for content entity types that have no storage.
 */
class ContentEntityNullStorage extends ContentEntityStorageBase {

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.null';
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    return $as_bool ? FALSE : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    return FALSE;
  }

}

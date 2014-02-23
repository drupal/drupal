<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\FieldableNullStorageController.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Query\QueryException;
use Drupal\field\FieldInstanceConfigInterface;

/**
 * Defines a null entity controller class.
 *
 * Used for content entity types that have no storage.
 */
class FieldableNullStorageController extends FieldableEntityStorageControllerBase {

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    return array();
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
  public function loadByProperties(array $values = array()) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryServiceName() {
    throw new QueryException('Null implementation can not be queried.');
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadFieldItems($entities, $age) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(EntityInterface $entity, $update) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems(EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItemsRevision(EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(EntityInterface $entity, FieldInstanceConfigInterface $instance) {
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(EntityInterface $entity, FieldInstanceConfigInterface $instance) {
  }

}

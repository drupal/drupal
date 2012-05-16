<?php

/**
 * @file
 * Definition of Drupal\entity\EntityStorageControllerInterface.
 */

namespace Drupal\entity;

/**
 * Defines a common interface for entity storage controllers.
 */
interface EntityStorageControllerInterface extends EntityControllerInterface {

  /**
   * Constructs a new entity object, without permanently saving it.
   *
   * @param $values
   *   An array of values to set, keyed by property name. If the entity type has
   *   bundles the bundle key has to be specified.
   *
   * @return Drupal\entity\EntityInterface
   *   A new entity object.
   */
  public function create(array $values);

  /**
   * Deletes permanently saved entities.
   *
   * @param $ids
   *   An array of entity IDs.
   *
   * @throws Drupal\entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function delete($ids);

  /**
   * Saves the entity permanently.
   *
   * @param Drupal\entity\EntityInterface $entity
   *   The entity to save.
   *
   * @return
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   *
   * @throws Drupal\entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function save(EntityInterface $entity);

}

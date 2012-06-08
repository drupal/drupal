<?php

/**
 * @file
 * Definition of Drupal\entity\EntityStorageControllerInterface.
 */

namespace Drupal\entity;

/**
 * Defines a common interface for entity controller classes.
 *
 * All entity controller classes specified via the 'controller class' key
 * returned by hook_entity_info() or hook_entity_info_alter() have to implement
 * this interface.
 *
 * Most simple, SQL-based entity controllers will do better by extending
 * Drupal\entity\DatabaseStorageController instead of implementing this
 * interface directly.
 */
interface EntityStorageControllerInterface {

  /**
   * Constructs a new Drupal\entity\EntityStorageControllerInterface object.
   *
   * @param $entityType
   *   The entity type for which the instance is created.
   */
  public function __construct($entityType);

  /**
   * Resets the internal, static entity cache.
   *
   * @param $ids
   *   (optional) If specified, the cache is reset for the entities with the
   *   given ids only.
   */
  public function resetCache(array $ids = NULL);

  /**
   * Loads one or more entities.
   *
   * @param $ids
   *   An array of entity IDs, or FALSE to load all entities.
   * @param $conditions
   *   An array of conditions in the form 'field' => $value.
   *
   * @return
   *   An array of entity objects indexed by their ids.
   */
  public function load($ids = array(), $conditions = array());

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

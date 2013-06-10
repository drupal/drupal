<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityStorageControllerInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines a common interface for entity controller classes.
 *
 * All entity controller classes specified via the "controllers['storage']" key
 * returned by \Drupal\Core\Entity\EntityManager or hook_entity_info_alter()
 * have to implement this interface.
 *
 * Most simple, SQL-based entity controllers will do better by extending
 * Drupal\Core\Entity\DatabaseStorageController instead of implementing this
 * interface directly.
 */
interface EntityStorageControllerInterface {

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
   *
   * @return
   *   An array of entity objects indexed by their ids.
   */
  public function load(array $ids = NULL);

  /**
   * Loads an unchanged entity from the database.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unchanged entity, or FALSE if the entity cannot be loaded.
   *
   * @todo Remove this method once we have a reliable way to retrieve the
   *   unchanged entity from the entity object.
   */
  public function loadUnchanged($id);

  /**
   * Load a specific entity revision.
   *
   * @param int $revision_id
   *   The revision id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The specified entity revision or FALSE if not found.
   */
  public function loadRevision($revision_id);

  /**
   * Delete a specific entity revision.
   *
   * A revision can only be deleted if it's not the currently active one.
   *
   * @param int $revision_id
   *   The revision id.
   */
  public function deleteRevision($revision_id);

  /**
   * Load entities by their property values.
   *
   * @param array $values
   *   An associative array where the keys are the property names and the
   *   values are the values those properties must have.
   *
   * @return array
   *   An array of entity objects indexed by their ids.
   */
  public function loadByProperties(array $values = array());

  /**
   * Constructs a new entity object, without permanently saving it.
   *
   * @param $values
   *   An array of values to set, keyed by property name. If the entity type has
   *   bundles the bundle key has to be specified.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity object.
   */
  public function create(array $values);

  /**
   * Deletes permanently saved entities.
   *
   * @param array $entities
   *   An array of entity objects to delete.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function delete(array $entities);

  /**
   * Saves the entity permanently.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   *
   * @return
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function save(EntityInterface $entity);

  /**
   * Gets an array of entity field definitions.
   *
   * If a 'bundle' key is present in the given entity definition, fields
   * specific to this bundle are included.
   * Entity fields are always multi-valued, so 'list' is TRUE for each
   * returned field definition.
   *
   * @param array $constraints
   *   An array of entity constraints as used for entities in typed data
   *   definitions, i.e. an array having an 'entity type' and optionally a
   *   'bundle' key. For example:
   *   @code
   *   array(
   *     'EntityType' => 'node',
   *     'Bundle' => 'article',
   *   )
   *   @endcode
   *
   * @return array
   *   An array of field definitions of entity fields, keyed by field
   *   name. In addition to the typed data definition keys as described at
   *   \Drupal::typedData()->create() the follow keys are supported:
   *   - queryable: Whether the field is queryable via QueryInterface.
   *     Defaults to TRUE if 'computed' is FALSE or not set, to FALSE otherwise.
   *   - translatable: Whether the field is translatable. Defaults to FALSE.
   *   - configurable: A boolean indicating whether the field is configurable
   *     via field.module. Defaults to FALSE.
   *   - property_constraints: An array of constraint arrays applying to the
   *     field item properties, keyed by property name. E.g. the following
   *     validates the value property to have a maximum length of 128:
   *     @code
   *     array(
   *       'value' => array('Length' => array('max' => 128)),
   *     )
   *     @endcode
   *
   * @see Drupal\Core\TypedData\TypedDataManager::create()
   * @see \Drupal::typedData()
   */
  public function getFieldDefinitions(array $constraints);

  /**
   * Gets the name of the service for the query for this entity storage.
   *
   * @return string
   */
  public function getQueryServicename();

}

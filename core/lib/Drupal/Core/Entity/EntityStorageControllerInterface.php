<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\EntityStorageControllerInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines a common interface for entity controller classes.
 *
 * All entity controller classes specified via the 'controller class' key
 * returned by hook_entity_info() or hook_entity_info_alter() have to implement
 * this interface.
 *
 * Most simple, SQL-based entity controllers will do better by extending
 * Drupal\Core\Entity\DatabaseStorageController instead of implementing this
 * interface directly.
 */
interface EntityStorageControllerInterface {

  /**
   * Constructs a new Drupal\Core\Entity\EntityStorageControllerInterface object.
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
   *
   * @return
   *   An array of entity objects indexed by their ids.
   */
  public function load(array $ids = NULL);

  /**
   * Load a specific entity revision.
   *
   * @param int $revision_id
   *   The revision id.
   *
   * @return Drupal\Core\Entity\EntityInterface|false
   *   The specified entity revision or FALSE if not found.
   */
  public function loadRevision($revision_id);

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
  public function loadByProperties(array $values);

  /**
   * Constructs a new entity object, without permanently saving it.
   *
   * @param $values
   *   An array of values to set, keyed by property name. If the entity type has
   *   bundles the bundle key has to be specified.
   *
   * @return Drupal\Core\Entity\EntityInterface
   *   A new entity object.
   */
  public function create(array $values);

  /**
   * Deletes permanently saved entities.
   *
   * @param $ids
   *   An array of entity IDs.
   *
   * @throws Drupal\Core\Entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function delete($ids);

  /**
   * Saves the entity permanently.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   *
   * @return
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   *
   * @throws Drupal\Core\Entity\EntityStorageException
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
   *     'entity type' => 'node',
   *     'bundle' => 'article',
   *   )
   *   @endcode
   *
   * @return array
   *   An array of field definitions of entity fields, keyed by field
   *   name. In addition to the typed data definition keys as described at
   *   typed_data()->create() the follow keys are supported:
   *   - queryable: Whether the field is queryable via EntityFieldQuery.
   *     Defaults to TRUE if 'computed' is FALSE or not set, to FALSE otherwise.
   *   - translatable: Whether the field is translatable. Defaults to FALSE.
   *   - configurable: A boolean indicating whether the field is configurable
   *     via field.module. Defaults to FALSE.
   *
   * @see Drupal\Core\TypedData\TypedDataManager::create()
   * @see typed_data()
   */
  public function getFieldDefinitions(array $constraints);
}

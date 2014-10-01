<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityStorageInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines the interface for entity storage classes.
 *
 * For common default implementations, see
 * \Drupal\Core\Entity\Sql\SqlContentEntityStorage for content entities and
 * \Drupal\Core\Config\Entity\ConfigEntityStorage for config entities. Those
 * implementations are used by default when the @ContentEntityType or
 * @ConfigEntityType annotations are used.
 *
 * @ingroup entity_api
 */
interface EntityStorageInterface {

  /**
   * Load the most recent version of an entity's field data.
   */
  const FIELD_LOAD_CURRENT = 'FIELD_LOAD_CURRENT';

  /**
   * Load the version of an entity's field data specified in the entity.
   */
  const FIELD_LOAD_REVISION = 'FIELD_LOAD_REVISION';

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
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities found.
   */
  public function loadMultiple(array $ids = NULL);

  /**
   * Loads one entity.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function load($id);

  /**
   * Loads an unchanged entity from the database.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The unchanged entity, or NULL if the entity cannot be loaded.
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
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity object.
   */
  public function create(array $values = array());

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
   * Gets the name of the service for the query for this entity storage.
   *
   * @return string
   *   The name of the service for the query for this entity storage.
   */
  public function getQueryServicename();

  /**
   * Returns an entity query instance.
   *
   * @param string $conjunction
   *   (optional) The logical operator for the query, either:
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query instance.
   *
   * @see \Drupal\Core\Entity\EntityStorageInterface::getQueryServicename()
   */
  public function getQuery($conjunction = 'AND');

  /**
   * Returns the entity type ID.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Returns the entity type definition.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   Entity type definition.
   */
  public function getEntityType();

}

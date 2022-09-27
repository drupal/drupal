<?php

namespace Drupal\Core\Entity;

/**
 * Provides methods for an entity to support revisions.
 *
 * Classes implementing this interface do not necessarily support revisions.
 *
 * To detect whether an entity type supports revisions, call
 * EntityTypeInterface::isRevisionable().
 *
 * Many entity interfaces are composed of numerous other interfaces such as this
 * one, which allow implementations to pick and choose which features to.
 * support through stub implementations of various interface methods. This means
 * that even if an entity class implements RevisionableInterface, it might only
 * have a stub implementation and not a functional one.
 *
 * @see \Drupal\Core\Entity\EntityTypeInterface::isRevisionable()
 * @see https://www.drupal.org/docs/8/api/entity-api/structure-of-an-entity-annotation
 * @see https://www.drupal.org/docs/8/api/entity-api/making-an-entity-revisionable
 */
interface RevisionableInterface extends EntityInterface {

  /**
   * Determines whether a new revision should be created on save.
   *
   * @return bool
   *   TRUE if a new revision should be created.
   *
   * @see \Drupal\Core\Entity\EntityInterface::setNewRevision()
   */
  public function isNewRevision();

  /**
   * Enforces an entity to be saved as a new revision.
   *
   * @param bool $value
   *   (optional) Whether a new revision should be saved.
   *
   * @throws \LogicException
   *   Thrown if the entity does not support revisions.
   *
   * @see \Drupal\Core\Entity\EntityInterface::isNewRevision()
   */
  public function setNewRevision($value = TRUE);

  /**
   * Gets the revision identifier of the entity.
   *
   * @return int|null|string
   *   The revision identifier of the entity, or NULL if the entity does not
   *   have a revision identifier.
   */
  public function getRevisionId();

  /**
   * Gets the loaded Revision ID of the entity.
   *
   * @return int
   *   The loaded Revision identifier of the entity, or NULL if the entity
   *   does not have a revision identifier.
   */
  public function getLoadedRevisionId();

  /**
   * Updates the loaded Revision ID with the revision ID.
   *
   * This method should not be used, it could unintentionally cause the original
   * revision ID property value to be lost.
   *
   * @internal
   *
   * @return $this
   */
  public function updateLoadedRevisionId();

  /**
   * Checks if this entity is the default revision.
   *
   * @param bool $new_value
   *   (optional) A Boolean to (re)set the isDefaultRevision flag.
   *
   * @return bool
   *   TRUE if the entity is the default revision, FALSE otherwise. If
   *   $new_value was passed, the previous value is returned.
   */
  public function isDefaultRevision($new_value = NULL);

  /**
   * Checks whether the entity object was a default revision when it was saved.
   *
   * @return bool
   *   TRUE if the entity object was a revision, FALSE otherwise.
   */
  public function wasDefaultRevision();

  /**
   * Checks if this entity is the latest revision.
   *
   * @return bool
   *   TRUE if the entity is the latest revision, FALSE otherwise.
   */
  public function isLatestRevision();

  /**
   * Acts on a revision before it gets saved.
   *
   * @param EntityStorageInterface $storage
   *   The entity storage object.
   * @param object $record
   *   The revision object.
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record);

}

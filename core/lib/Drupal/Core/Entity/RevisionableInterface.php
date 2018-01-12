<?php

namespace Drupal\Core\Entity;

/**
 * Provides methods for an entity to support revisions.
 */
interface RevisionableInterface {

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
   * @return
   *   The revision identifier of the entity, or NULL if the entity does not
   *   have a revision identifier.
   */
  public function getRevisionId();

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
   * @param \stdClass $record
   *   The revision object.
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record);

}

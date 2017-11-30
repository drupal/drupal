<?php

namespace Drupal\Core\Entity;

/**
 * A storage that supports revisionable entity types.
 */
interface RevisionableStorageInterface {

  /**
   * Loads a specific entity revision.
   *
   * @param int $revision_id
   *   The revision ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The specified entity revision or NULL if not found.
   */
  public function loadRevision($revision_id);

  /**
   * Loads multiple entity revisions.
   *
   * @param array $revision_ids
   *   An array of revision IDs to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity revisions keyed by their revision ID, or an empty
   *   array if none found.
   */
  public function loadMultipleRevisions(array $revision_ids);

  /**
   * Deletes a specific entity revision.
   *
   * A revision can only be deleted if it's not the currently active one.
   *
   * @param int $revision_id
   *   The revision ID.
   */
  public function deleteRevision($revision_id);

}

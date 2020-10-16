<?php

namespace Drupal\history;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface to store and retrieve a last view timestamp of entities.
 */
interface HistoryRepositoryInterface {

  /**
   * Retrieves the timestamp for the current user's last view of the entities.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $entity_ids
   *   The entity IDs.
   *
   * @return array
   *   Array of timestamps keyed by entity ID. If a entity has been previously
   *   viewed by the user, the timestamp in seconds of when the last view
   *   occurred.
   */
  public function getLastViewed(string $entity_type, array $entity_ids): array;

  /**
   * Updates 'last viewed' timestamp of the entity for the user account.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that history should be updated.
   *
   * @return self
   */
  public function updateLastViewed(EntityInterface $entity): HistoryRepositoryInterface;

  /**
   * Gets an array of cache tags for the history timestamp.
   *
   * @param int|string $user_id
   *   The User ID.
   * @param int|string $entity_id
   *   The entity ID.
   *
   * @return string[]
   *   An array of cache tags based on the current view.
   */
  public function getCacheTags($user_id, $entity_id): array;

  /**
   * Purges outdated history.
   */
  public function purge(): void;

  /**
   * Deletes the history for the given user account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to purge history.
   */
  public function deleteByUser(AccountInterface $account): void;

  /**
   * Deletes the history for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that history should be deleted.
   */
  public function deleteByEntity(EntityInterface $entity): void;

}

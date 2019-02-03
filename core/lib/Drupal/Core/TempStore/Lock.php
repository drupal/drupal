<?php

namespace Drupal\Core\TempStore;

/**
 * Provides a value object representing the lock from a TempStore.
 */
final class Lock {

  /**
   * The owner ID.
   *
   * @var int
   */
  private $ownerId;

  /**
   * The timestamp the lock was last updated.
   *
   * @var int
   */
  private $updated;

  /**
   * Constructs a new Lock object.
   *
   * @param int $owner_id
   *   The owner ID.
   * @param int $updated
   *   The updated timestamp.
   */
  public function __construct($owner_id, $updated) {
    $this->ownerId = $owner_id;
    $this->updated = $updated;
  }

  /**
   * Gets the owner ID.
   *
   * @return int
   *   The owner ID.
   */
  public function getOwnerId() {
    return $this->ownerId;
  }

  /**
   * Gets the timestamp of the last update to the lock.
   *
   * @return int
   *   The updated timestamp.
   */
  public function getUpdated() {
    return $this->updated;
  }

  /**
   * Provides backwards compatibility for using the lock as a \stdClass object.
   */
  public function __get($name) {
    if ($name === 'owner') {
      @trigger_error('Using the "owner" public property of a TempStore lock is deprecated in Drupal 8.7.0 and will not be allowed in Drupal 9.0.0. Use \Drupal\Core\TempStore\Lock::getOwnerId() instead. See https://www.drupal.org/node/3025869.', E_USER_DEPRECATED);
      return $this->getOwnerId();
    }
    if ($name === 'updated') {
      @trigger_error('Using the "updated" public property of a TempStore lock is deprecated in Drupal 8.7.0 and will not be allowed in Drupal 9.0.0. Use \Drupal\Core\TempStore\Lock::getUpdated() instead. See https://www.drupal.org/node/3025869.', E_USER_DEPRECATED);
      return $this->getUpdated();
    }
    throw new \InvalidArgumentException($name);
  }

}

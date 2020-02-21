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

}

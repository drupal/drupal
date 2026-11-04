<?php

namespace Drupal\Core\TempStore;

/**
 * Provides a value object representing the lock from a TempStore.
 */
final class Lock {

  /**
   * Constructs a new Lock object.
   *
   * @param int $ownerId
   *   The owner ID.
   * @param int $updated
   *   The updated timestamp.
   */
  public function __construct(
    private $ownerId,
    private $updated,
  ) {
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

<?php

/**
 * @file
 * Contains \Drupal\Core\Lock\PersistentDatabaseLockBackend.
 */

namespace Drupal\Core\Lock;

use Drupal\Core\Database\Connection;

/**
 * Defines the persistent database lock backend. This backend is global for this
 * Drupal installation.
 *
 * @ingroup lock
 */
class PersistentDatabaseLockBackend extends DatabaseLockBackend {

  /**
   * Constructs a new PersistentDatabaseLockBackend.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    // Do not call the parent constructor to avoid registering a shutdown
    // function that releases all the locks at the end of a request.
    $this->database = $database;
    // Set the lockId to a fixed string to make the lock ID the same across
    // multiple requests. The lock ID is used as a page token to relate all the
    // locks set during a request to each other.
    // @see \Drupal\Core\Lock\LockBackendInterface::getLockId()
    $this->lockId = 'persistent';
  }
}

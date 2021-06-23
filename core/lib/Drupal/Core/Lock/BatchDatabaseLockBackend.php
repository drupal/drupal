<?php

namespace Drupal\Core\Lock;

use Drupal\Core\Database\Connection;

/**
 * Defines a lock backend for batches.
 *
 * @ingroup lock
 */
class BatchDatabaseLockBackend extends DatabaseLockBackend {

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    // Do not call the parent constructor to avoid registering a shutdown
    // function that releases all the locks at the end of a request.
    $this->database = $database;

    // Try and load locks owned by this batch process.
    $query = $this->database->select('semaphore', 's');
    $query->addField('s', 'name');
    $query->condition('value', $this->getLockId());
    try {
      $results = $query->execute()->fetchCol();
      $this->locks = array_fill_keys($results, TRUE);
    }
    catch (\Exception $e) {
      $this->ensureTableExists();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getLockId() {
    if (!isset($this->lockId)) {
      $this->lockId = batch_get()['id'] ?? uniqid(mt_rand(), TRUE);
    }
    return $this->lockId;
  }

}

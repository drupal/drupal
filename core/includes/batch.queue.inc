<?php

/**
 * @file
 * Queue handlers used by the Batch API.
 *
 * These implementations:
 * - Ensure FIFO ordering.
 * - Allow an item to be repeatedly claimed until it is actually deleted (no
 *   notion of lease time or 'expire' date), to allow multipass operations.
 */

/**
 * Defines a batch queue.
 *
 * Stale items from failed batches are cleaned from the {queue} table on cron
 * using the 'created' date.
 */
class BatchQueue extends SystemQueue {

  /**
   * Overrides SystemQueue::claimItem().
   *
   * Unlike SystemQueue::claimItem(), this method provides a default lease
   * time of 0 (no expiration) instead of 30. This allows the item to be
   * claimed repeatedly until it is deleted.
   */
  public function claimItem($lease_time = 0) {
    $item = db_query_range('SELECT data, item_id FROM {queue} q WHERE name = :name ORDER BY item_id ASC', 0, 1, array(':name' => $this->name))->fetchObject();
    if ($item) {
      $item->data = unserialize($item->data);
      return $item;
    }
    return FALSE;
  }

  /**
   * Retrieves all remaining items in the queue.
   *
   * This is specific to Batch API and is not part of the DrupalQueueInterface.
   */
  public function getAllItems() {
    $result = array();
    $items = db_query('SELECT data FROM {queue} q WHERE name = :name ORDER BY item_id ASC', array(':name' => $this->name))->fetchAll();
    foreach ($items as $item) {
      $result[] = unserialize($item->data);
    }
    return $result;
  }
}

/**
 * Defines a batch queue for non-progressive batches.
 */
class BatchMemoryQueue extends MemoryQueue {

  /**
   * Overrides MemoryQueue::claimItem().
   *
   * Unlike MemoryQueue::claimItem(), this method provides a default lease
   * time of 0 (no expiration) instead of 30. This allows the item to be
   * claimed repeatedly until it is deleted.
   */
  public function claimItem($lease_time = 0) {
    if (!empty($this->queue)) {
      reset($this->queue);
      return current($this->queue);
    }
    return FALSE;
  }

  /**
   * Retrieves all remaining items in the queue.
   *
   * This is specific to Batch API and is not part of the DrupalQueueInterface.
   */
  public function getAllItems() {
    $result = array();
    foreach ($this->queue as $item) {
      $result[] = $item->data;
    }
    return $result;
  }
}

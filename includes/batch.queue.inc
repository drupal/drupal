<?php
// $Id: batch.queue.inc,v 1.1 2010/01/08 06:36:34 webchick Exp $


/**
 * @file
 * Queue handlers used by the Batch API.
 *
 * Those implementations:
 * - ensure FIFO ordering,
 * - let an item be repeatedly claimed until it is actually deleted (no notion
 *   of lease time or 'expire' date), to allow multipass operations.
 */

/**
 * Batch queue implementation.
 *
 * Stale items from failed batches are cleaned from the {queue} table on cron
 * using the 'created' date.
 */
class BatchQueue extends SystemQueue {

  public function claimItem($lease_time = 0) {
    $item = db_query('SELECT data, item_id FROM {queue} q WHERE name = :name ORDER BY item_id ASC', array(':name' => $this->name))->fetchObject();
    if ($item) {
      $item->data = unserialize($item->data);
      return $item;
    }
    return FALSE;
  }

  /**
   * Retrieve all remaining items in the queue.
   *
   * This is specific to Batch API and is not part of the DrupalQueueInterface,
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
 * Batch queue implementation used for non-progressive batches.
 */
class BatchMemoryQueue extends MemoryQueue {

  public function claimItem($lease_time = 0) {
    if (!empty($this->queue)) {
      reset($this->queue);
      return current($this->queue);
    }
    return FALSE;
  }

  /**
   * Retrieve all remaining items in the queue.
   *
   * This is specific to Batch API and is not part of the DrupalQueueInterface,
   */
  public function getAllItems() {
    $result = array();
    foreach ($this->queue as $item) {
      $result[] = $item->data;
    }
    return $result;
  }
}

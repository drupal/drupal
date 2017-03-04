<?php

namespace Drupal\Core\Queue;

/**
 * Defines a batch queue handler used by the Batch API for non-progressive
 * batches.
 *
 * This implementation:
 * - Ensures FIFO ordering.
 * - Allows an item to be repeatedly claimed until it is actually deleted (no
 *   notion of lease time or 'expire' date), to allow multipass operations.
 *
 * @ingroup queue
 */
class BatchMemory extends Memory {

  /**
   * Overrides \Drupal\Core\Queue\Memory::claimItem().
   *
   * Unlike \Drupal\Core\Queue\Memory::claimItem(), this method provides a
   * default lease time of 0 (no expiration) instead of 30. This allows the item
   * to be claimed repeatedly until it is deleted.
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
   * This is specific to Batch API and is not part of the
   * \Drupal\Core\Queue\QueueInterface.
   *
   * @return array
   *   An array of queue items.
   */
  public function getAllItems() {
    $result = [];
    foreach ($this->queue as $item) {
      $result[] = $item->data;
    }
    return $result;
  }

}

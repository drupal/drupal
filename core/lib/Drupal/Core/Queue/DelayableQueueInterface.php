<?php

namespace Drupal\Core\Queue;

/**
 * Delayable queue interface.
 *
 * Classes implementing this interface allow an item to be released on a delay.
 *
 * @ingroup queue
 */
interface DelayableQueueInterface extends QueueInterface {

  /**
   * Delay an item so it runs in the future.
   *
   * @param object $item
   *   The item returned by \Drupal\Core\Queue\QueueInterface::claimItem().
   * @param int $delay
   *   A delay before the item's lock should expire (in seconds). Relative to
   *   the current time, not the item's current expiry.
   *
   * @throws \InvalidArgumentException
   *   When a negative $delay is provided; $delay must be non-negative.
   *
   * @see \Drupal\Core\Queue\QueueInterface::releaseItem()
   *   To immediately release an item without delay.
   *
   * @return bool
   *   TRUE if the item has been updated, FALSE otherwise.
   */
  public function delayItem($item, int $delay);

}

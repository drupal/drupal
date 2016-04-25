<?php

namespace Drupal\Core\Queue;

/**
 * If the Drupal 'queue' service implements this interface, the
 * garbageCollection() method will be called during cron.
 *
 * @see system_cron()
 */
interface QueueGarbageCollectionInterface {

  /**
   * Cleans queues of garbage.
   */
  public function garbageCollection();

}

<?php

declare(strict_types=1);

namespace Drupal\Core\Queue;

use Drupal\Core\Utility\Error;

/**
 * Provides a trait for processing queue items.
 */
trait QueueProcessTrait {

  /**
   * Process a queue item.
   *
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue.
   * @param \Drupal\Core\Queue\QueueWorkerInterface $worker
   *   The queue worker.
   *
   * @return bool
   *   TRUE if an item was claimed, FALSE if not.
   *
   * @throws \Drupal\Core\Queue\SuspendQueueException
   *   If the queue was suspended.
   */
  protected function processItem(QueueInterface $queue, QueueWorkerInterface $worker): bool {
    $claimed = FALSE;
    $lease_time = $worker->getPluginDefinition()['cron']['time'] ?? NULL;
    if ($item = $lease_time === NULL ? $queue->claimItem() : $queue->claimItem($lease_time)) {
      $claimed = TRUE;
      try {
        $worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (DelayedRequeueException $e) {
        // The worker requested the task not be immediately re-queued.
        // - If the queue doesn't support ::delayItem(), we should leave the
        // item's current expiry time alone.
        // - If the queue does support ::delayItem(), we should allow the
        // queue to update the item's expiry using the requested delay.
        if ($queue instanceof DelayableQueueInterface) {
          // This queue can handle a custom delay; use the duration provided
          // by the exception.
          $queue->delayItem($item, $e->getDelay());
        }
      }
      catch (RequeueException) {
        // The worker requested the task be immediately requeued.
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates the whole queue should be skipped, release
        // the item and go to the next queue.
        $queue->releaseItem($item);

        $this->logger->debug('A worker for @queue queue suspended further processing of the queue.', [
          '@queue' => $worker->getPluginId(),
        ]);

        // Skip to the next queue.
        throw $e;
      }
      catch (\Throwable $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        Error::logException($this->logger, $e);
      }
    }
    return $claimed;
  }

}

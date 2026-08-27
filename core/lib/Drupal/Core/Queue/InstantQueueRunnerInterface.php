<?php

declare(strict_types=1);

namespace Drupal\Core\Queue;

/**
 * Provides an interface for adding queues to be run instantly.
 */
interface InstantQueueRunnerInterface {

  /**
   * Registers a queue to be run by the instant queue runner.
   *
   * @param string $queue_name
   *   The queue name.
   */
  public function registerQueue(string $queue_name): void;

}

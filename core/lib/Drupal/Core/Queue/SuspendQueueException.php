<?php

namespace Drupal\Core\Queue;

/**
 * Exception class to throw to indicate that a cron queue should be skipped.
 *
 * An implementation of \Drupal\Core\Queue\QueueWorkerInterface::processItem()
 * throws this class of exception to indicate that processing of the whole queue
 * should be skipped. This should be thrown rather than a normal Exception if
 * the problem encountered by the queue worker is such that it can be deduced
 * that workers of subsequent items would encounter it too. For example, if a
 * remote site that the queue worker depends on appears to be inaccessible.
 */
class SuspendQueueException extends \RuntimeException {

  /**
   * Seconds to wait before resuming the queue, or NULL if unknown.
   *
   * @var float|null
   */
  protected $delay = NULL;

  /**
   * Constructs a SuspendQueueException.
   *
   * @param string $message
   *   The error message.
   * @param int $code
   *   The error code.
   * @param \Throwable|null $previous
   *   The previous throwable used for the exception chaining.
   * @param float|null $delay
   *   If the time for when the queue will be ready to resume processing is
   *   known, pass an interval in seconds. Otherwise NULL if the time to resume
   *   processing the queue is not known.
   */
  public function __construct(string $message = '', int $code = 0, \Throwable $previous = NULL, ?float $delay = NULL) {
    parent::__construct($message, $code, $previous);
    $this->delay = $delay;
  }

  /**
   * Get the desired delay interval for this item.
   *
   * @return float|null
   *   If the time for when the queue will be ready to resume processing is
   *   known, pass an interval in seconds. Otherwise NULL if the time to resume
   *   processing the queue is not known.
   */
  public function getDelay(): ?float {
    return $this->delay;
  }

  /**
   * Determine whether the next time the queue should be checked is known.
   *
   * @return bool
   *   Whether the time to resume processing the queue is known.
   */
  public function isDelayable(): bool {
    return isset($this->delay);
  }

}

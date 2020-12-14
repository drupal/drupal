<?php

namespace Drupal\Core\Queue;

/**
 * Throw this exception to leave an item in the queue until its lock expires.
 *
 * @see \Drupal\Core\Cron::processQueues()
 *   For more information about how this exception interacts with Drupal's queue
 *   processing via the built-in cron service.
 * @see \Drupal\Core\Queue\DelayableQueueInterface
 *   Queues must implement this interface to support custom delay intervals; if
 *   this interface is missing, any custom delay interval specified for this
 *   exception will be ignored and the remaining time in the original lease will
 *   be used as the duration of the delay interval.
 * @see \Drupal\Core\Queue\RequeueException
 *   For use when an item needs to be requeued immediately.
 */
class DelayedRequeueException extends \RuntimeException {

  /**
   * The interval of time that the item should remain locked (in seconds).
   *
   * @var int
   */
  protected $delay = 0;

  /**
   * Constructs a DelayedRequeueException.
   *
   * @param int $delay
   *   The desired delay interval for this item (in seconds).
   * @param string $message
   *   The error message.
   * @param int $code
   *   The error code.
   * @param \Throwable|null $previous
   *   The previous throwable used for the exception chaining.
   */
  public function __construct(int $delay = 0, string $message = '', int $code = 0, \Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
    if ($delay > 0) {
      $this->delay = $delay;
    }
  }

  /**
   * Get the desired delay interval for this item.
   *
   * @see self::$delay
   *   For recommended value usage in a queue processor.
   *
   * @return int
   *   The desired delay interval for this item.
   */
  public function getDelay(): int {
    return $this->delay;
  }

}

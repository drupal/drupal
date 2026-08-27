<?php

declare(strict_types=1);

namespace Drupal\Core\Queue;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides an instant queue runner that processes items in kernel.destruct.
 */
class InstantQueueRunner implements InstantQueueRunnerInterface, DestructableInterface {
  use QueueProcessTrait;

  /**
   * An array of integers keyed by queue name.
   *
   * Tracks the number of items registered per queue during the request.
   */
  protected array $queues = [];

  public function __construct(
    protected QueueFactory $queueFactory,
    protected QueueWorkerManagerInterface $queueManager,
    #[Autowire(service: 'logger.channel.queue')]
    protected LoggerChannelInterface $logger,
    #[Autowire(param: 'instant_queue.config')]
    protected array $config,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function registerQueue(string $queue_name): void {
    $this->queues[$queue_name] = ($this->queues[$queue_name] ?? 0) + 1;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct(): void {
    foreach ($this->queues as $queue_name => $count) {
      $queue = $this->queueFactory->get($queue_name);
      // Make sure every queue exists. There is no harm in trying to recreate
      // an existing queue.
      $queue->createQueue();
      $worker = $this->queueManager->createInstance($queue_name);
      $count = min($this->config['maximumItems'], $count);
      while ($count) {
        $count--;
        try {
          $claimed = $this->processItem($queue, $worker);
          if (!$claimed) {
            break;
          }
        }
        // If any item suspends the queue, stop processing this queue altogether
        // for this request.
        catch (SuspendQueueException) {
          break;
        }
      }
    }
  }

}

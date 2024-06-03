<?php

namespace Drupal\Core;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\DelayableQueueInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The Drupal core Cron service.
 */
class Cron implements CronInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The queue plugin manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The queue config.
   *
   * @var array
   */
  protected array $queueConfig;

  /**
   * Constructs a cron object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switching service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The queue plugin manager.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   * @param mixed[]|null $queue_config
   *   Queue configuration from the service container.
   */
  public function __construct(ModuleHandlerInterface $module_handler, LockBackendInterface $lock, QueueFactory $queue_factory, StateInterface $state, AccountSwitcherInterface $account_switcher, LoggerInterface $logger, QueueWorkerManagerInterface $queue_manager, ?TimeInterface $time = NULL, ?array $queue_config = NULL) {
    $this->moduleHandler = $module_handler;
    $this->lock = $lock;
    $this->queueFactory = $queue_factory;
    $this->state = $state;
    $this->accountSwitcher = $account_switcher;
    $this->logger = $logger;
    $this->queueManager = $queue_manager;
    if (!isset($time)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $time argument is deprecated in drupal:10.1.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3343743', E_USER_DEPRECATED);
      $time = \Drupal::service('datetime.time');
    }
    $this->time = $time;
    if (!isset($queue_config)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $queue_config argument is deprecated in drupal:10.1.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3343743', E_USER_DEPRECATED);
      $queue_config = \Drupal::getContainer()->getParameter('queue.config');
    }
    $this->queueConfig = $queue_config + [
      'suspendMaximumWait' => 30.0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Allow execution to continue even if the request gets cancelled.
    @ignore_user_abort(TRUE);

    // Force the current user to anonymous to ensure consistent permissions on
    // cron runs.
    $this->accountSwitcher->switchTo(new AnonymousUserSession());

    // Try to allocate enough time to run all the hook_cron implementations.
    Environment::setTimeLimit(240);

    $return = FALSE;

    // Try to acquire cron lock.
    if (!$this->lock->acquire('cron', 900.0)) {
      // Cron is still running normally.
      $this->logger->warning('Attempting to re-run cron while it is already running.');
    }
    else {
      $this->invokeCronHandlers();

      // Process cron queues.
      $this->processQueues();

      $this->setCronLastTime();

      // Release cron lock.
      $this->lock->release('cron');

      // Return TRUE so other functions can check if it did run successfully
      $return = TRUE;
    }

    // Restore the user.
    $this->accountSwitcher->switchBack();

    return $return;
  }

  /**
   * Records and logs the request time for this cron invocation.
   */
  protected function setCronLastTime() {
    // Record cron time.
    $request_time = $this->time->getRequestTime();
    $this->state->set('system.cron_last', $request_time);
    $this->logger->info('Cron run completed.');
  }

  /**
   * Processes cron queues.
   */
  protected function processQueues() {
    $max_wait = (float) $this->queueConfig['suspendMaximumWait'];

    // Build a stack of queues to work on.
    /** @var array<array{process_from: int<0, max>, queue: \Drupal\Core\Queue\QueueInterface, worker: \Drupal\Core\Queue\QueueWorkerInterface}> $queues */
    $queues = [];
    foreach ($this->queueManager->getDefinitions() as $queue_name => $queue_info) {
      if (!isset($queue_info['cron'])) {
        continue;
      }
      $queue = $this->queueFactory->get($queue_name);
      // Make sure every queue exists. There is no harm in trying to recreate
      // an existing queue.
      $queue->createQueue();
      $worker = $this->queueManager->createInstance($queue_name);
      $queues[] = [
        // Set process_from to zero so each queue is always processed
        // immediately for the first time. This process_from timestamp will
        // change if a queue throws a delayable SuspendQueueException.
        'process_from' => 0,
        'queue' => $queue,
        'worker' => $worker,
      ];
    }

    // Work through stack of queues, re-adding to the stack when a delay is
    // necessary.
    while ($item = array_shift($queues)) {
      [
        'queue' => $queue,
        'worker' => $worker,
        'process_from' => $process_from,
      ] = $item;

      // Each queue will be processed immediately when it is reached for the
      // first time, as zero > currentTime will never be true.
      if ($process_from > $this->time->getCurrentMicroTime()) {
        $this->usleep(round($process_from - $this->time->getCurrentMicroTime(), 3) * 1000000);
      }

      try {
        $this->processQueue($queue, $worker);
      }
      catch (SuspendQueueException $e) {
        // Return to this queue after processing other queues if the delay is
        // within the threshold.
        if ($e->isDelayable() && ($e->getDelay() < $max_wait)) {
          $item['process_from'] = $this->time->getCurrentMicroTime() + $e->getDelay();
          // Place this queue back in the stack for processing later.
          array_push($queues, $item);
        }
      }

      // Reorder the queue by next 'process_from' timestamp.
      usort($queues, function (array $queueA, array $queueB) {
        return $queueA['process_from'] <=> $queueB['process_from'];
      });
    }
  }

  /**
   * Processes a cron queue.
   *
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue.
   * @param \Drupal\Core\Queue\QueueWorkerInterface $worker
   *   The queue worker.
   *
   * @throws \Drupal\Core\Queue\SuspendQueueException
   *   If the queue was suspended.
   */
  protected function processQueue(QueueInterface $queue, QueueWorkerInterface $worker) {
    $lease_time = $worker->getPluginDefinition()['cron']['time'];
    $end = $this->time->getCurrentTime() + $lease_time;
    while ($this->time->getCurrentTime() < $end && ($item = $queue->claimItem($lease_time))) {
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
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        Error::logException($this->logger, $e);
      }
    }
  }

  /**
   * Invokes any cron handlers implementing hook_cron.
   */
  protected function invokeCronHandlers() {
    $module_previous = '';

    // If detailed logging isn't enabled, don't log individual execution times.
    $time_logging_enabled = \Drupal::config('system.cron')->get('logging');
    $logger = $time_logging_enabled ? $this->logger : new NullLogger();

    // Iterate through the modules calling their cron handlers (if any):
    $this->moduleHandler->invokeAllWith('cron', function (callable $hook, string $module) use (&$module_previous, $logger) {
      if (!$module_previous) {
        $logger->info('Starting execution of @module_cron().', [
          '@module' => $module,
        ]);
      }
      else {
        $logger->info('Starting execution of @module_cron(), execution of @module_previous_cron() took @time.', [
          '@module' => $module,
          '@module_previous' => $module_previous,
          '@time' => Timer::read('cron_' . $module_previous) . 'ms',
        ]);
      }
      Timer::start('cron_' . $module);

      // Do not let an exception thrown by one module disturb another.
      try {
        $hook();
      }
      catch (\Exception $e) {
        Error::logException($this->logger, $e);
      }

      Timer::stop('cron_' . $module);
      $module_previous = $module;
    });
    if ($module_previous) {
      $logger->info('Execution of @module_previous_cron() took @time.', [
        '@module_previous' => $module_previous,
        '@time' => Timer::read('cron_' . $module_previous) . 'ms',
      ]);
    }
  }

  /**
   * Delay execution in microseconds.
   *
   * @param int $microseconds
   *   Halt time in microseconds.
   */
  protected function usleep(int $microseconds): void {
    usleep($microseconds);
  }

}

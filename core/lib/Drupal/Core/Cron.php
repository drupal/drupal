<?php

/**
 * @file
 * Contains \Drupal\Core\Cron.
 */

namespace Drupal\Core;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\StateInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\UserSession;

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
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * Constructs a cron object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   * @param \Drupal\Core\KeyValueStore\StateInterface $state
   *   The state service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, LockBackendInterface $lock, QueueFactory $queue_factory, StateInterface $state) {
    $this->moduleHandler = $module_handler;
    $this->lock = $lock;
    $this->queueFactory = $queue_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Allow execution to continue even if the request gets cancelled.
    @ignore_user_abort(TRUE);

    // Prevent session information from being saved while cron is running.
    $original_session_saving = drupal_save_session();
    drupal_save_session(FALSE);

    // Force the current user to anonymous to ensure consistent permissions on
    // cron runs.
    // @todo This currently does not work, as it will not affect the current
    //   user being injected into services.
    $original_user = $GLOBALS['user'];
    $GLOBALS['user'] = new UserSession();

    // Try to allocate enough time to run all the hook_cron implementations.
    drupal_set_time_limit(240);

    $return = FALSE;
    // Grab the defined cron queues.
    $queues = $this->moduleHandler->invokeAll('queue_info');
    $this->moduleHandler->alter('queue_info', $queues);

    // Try to acquire cron lock.
    if (!$this->lock->acquire('cron', 240.0)) {
      // Cron is still running normally.
      watchdog('cron', 'Attempting to re-run cron while it is already running.', array(), WATCHDOG_WARNING);
    }
    else {
      // Make sure every queue exists. There is no harm in trying to recreate an
      // existing queue.
      foreach ($queues as $queue_name => $info) {
        if (isset($info['cron'])) {
          $this->queueFactory->get($queue_name)->createQueue();
        }
      }

      // Iterate through the modules calling their cron handlers (if any):
      foreach ($this->moduleHandler->getImplementations('cron') as $module) {
        // Do not let an exception thrown by one module disturb another.
        try {
          $this->moduleHandler->invoke($module, 'cron');
        }
        catch (\Exception $e) {
          watchdog_exception('cron', $e);
        }
      }

      // Record cron time.
      $this->state->set('system.cron_last', REQUEST_TIME);
      watchdog('cron', 'Cron run completed.', array(), WATCHDOG_NOTICE);

      // Release cron lock.
      $this->lock->release('cron');

      // Return TRUE so other functions can check if it did run successfully
      $return = TRUE;
    }

    foreach ($queues as $queue_name => $info) {
      if (isset($info['cron'])) {
        $callback = $info['worker callback'];
        $end = time() + (isset($info['cron']['time']) ? $info['cron']['time'] : 15);
        $queue = $this->queueFactory->get($queue_name);
        while (time() < $end && ($item = $queue->claimItem())) {
          try {
            call_user_func_array($callback, array($item->data));
            $queue->deleteItem($item);
          }
          catch (\Exception $e) {
            // In case of exception log it and leave the item in the queue
            // to be processed again later.
            watchdog_exception('cron', $e);
          }
        }
      }
    }

    // Restore the user.
    // @todo This currently does not work, as it will not affect the current
    //   user being injected into services.
    $GLOBALS['user'] = $original_user;
    drupal_save_session($original_session_saving);

    return $return;
  }

}

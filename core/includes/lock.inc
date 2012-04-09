<?php

/**
 * @file
 * Drupal lock framework procedural proxy implementation.
 */

/**
 * @defgroup lock Locking mechanisms
 * @{
 * Functions to coordinate long-running operations across requests.
 *
 * In most environments, multiple Drupal page requests (a.k.a. threads or
 * processes) will execute in parallel. This leads to potential conflicts or
 * race conditions when two requests execute the same code at the same time. A
 * common example of this is a rebuild like menu_rebuild() where we invoke many
 * hook implementations to get and process data from all active modules, and
 * then delete the current data in the database to insert the new afterwards.
 *
 * This is a cooperative, advisory lock system. Any long-running operation
 * that could potentially be attempted in parallel by multiple requests should
 * try to acquire a lock before proceeding. By obtaining a lock, one request
 * notifies any other requests that a specific operation is in progress which
 * must not be executed in parallel.
 *
 * To use this API, pick a unique name for the lock. A sensible choice is the
 * name of the function performing the operation. A very simple example use of
 * this API:
 * @code
 * function mymodule_long_operation() {
 *   if (lock_acquire('mymodule_long_operation')) {
 *     // Do the long operation here.
 *     // ...
 *     lock_release('mymodule_long_operation');
 *   }
 * }
 * @endcode
 *
 * If a function acquires a lock it should always release it when the
 * operation is complete by calling lock_release(), as in the example.
 *
 * A function that has acquired a lock may attempt to renew a lock (extend the
 * duration of the lock) by calling lock_acquire() again during the operation.
 * Failure to renew a lock is indicative that another request has acquired
 * the lock, and that the current operation may need to be aborted.
 *
 * If a function fails to acquire a lock it may either immediately return, or
 * it may call lock_wait() if the rest of the current page request requires
 * that the operation in question be complete. After lock_wait() returns,
 * the function may again attempt to acquire the lock, or may simply allow the
 * page request to proceed on the assumption that a parallel request completed
 * the operation.
 *
 * lock_acquire() and lock_wait() will automatically break (delete) a lock
 * whose duration has exceeded the timeout specified when it was acquired.
 */

use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Get locking layer instance.
 *
 * @return Drupal\Core\Lock\LockBackendInterface
 */
function lock() {
  $lock_backend = &drupal_static(__FUNCTION__);

  if (!isset($lock_backend)) {
    $class_name = variable_get('lock_backend', 'Drupal\Core\Lock\DatabaseLockBackend');

    // Do not allow a WSOD here, if the class does not exists use the default
    // one.
    // @todo: We should log failed class loading for debugging, but for that we
    // need an early watchdog function that logs into a file if the database is
    // not present.
    if (class_exists($class_name)) {
      $lock_backend = new $class_name();
    }
    else {
      $lock_backend = new DatabaseLockBackend();
    }

    drupal_register_shutdown_function(array($lock_backend, 'releaseAll'));
  }

  return $lock_backend;
}

/**
 * Acquire (or renew) a lock, but do not block if it fails.
 *
 * @param $name
 *   The name of the lock.
 * @param $timeout
 *   A number of seconds (float) before the lock expires (minimum of 0.001).
 *
 * @return
 *   TRUE if the lock was acquired, FALSE if it failed.
 *
 * @deprecated
 */
function lock_acquire($name, $timeout = 30.0) {
  return lock()->acquire($name, $timeout);
}

/**
 * Wait for a lock to be available.
 *
 * This function may be called in a request that fails to acquire a desired
 * lock. This will block further execution until the lock is available or the
 * specified delay in seconds is reached. This should not be used with locks
 * that are acquired very frequently, since the lock is likely to be acquired
 * again by a different request while waiting.
 *
 * @param $name
 *   The name of the lock.
 * @param $delay
 *   The maximum number of seconds to wait, as an integer.
 *
 * @return
 *   TRUE if the lock holds, FALSE if it is available.
 *
 * @deprecated
 */
function lock_wait($name, $delay = 30) {
  return lock()->wait($name, $delay);
}

/**
 * Release a lock previously acquired by lock_acquire().
 *
 * This will release the named lock if it is still held by the current request.
 *
 * @param $name
 *   The name of the lock.
 *
 * @deprecated
 */
function lock_release($name) {
  lock()->release($name);
}

/**
 * Release all previously acquired locks.
 *
 * @deprecated
 */
function lock_release_all($lock_id = NULL) {
  lock()->releaseAll($lock_id);
}

/**
 * @} End of "defgroup lock".
 */

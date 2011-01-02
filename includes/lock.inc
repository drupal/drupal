<?php
// $Id$

/**
 * @file
 * A database-mediated implementation of a locking mechanism.
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
 *
 * Alternative implementations of this API (such as APC) may be substituted
 * by setting the 'lock_inc' variable to an alternate include filepath. Since
 * this is an API intended to support alternative implementations, code using
 * this API should never rely upon specific implementation details (for example
 * no code should look for or directly modify a lock in the {semaphore} table).
 */

/**
 * Initialize the locking system.
 */
function lock_initialize() {
  global $locks;

  $locks = array();
}

/**
 * Helper function to get this request's unique id.
 */
function _lock_id() {
  $lock_id = &drupal_static(__FUNCTION__);

  if (!isset($lock_id)) {
    // Assign a unique id.
    $lock_id = uniqid(mt_rand(), TRUE);
    // We only register a shutdown function if a lock is used.
    drupal_register_shutdown_function('lock_release_all', $lock_id);
  }
  return $lock_id;
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
 */
function lock_acquire($name, $timeout = 30.0) {
  global $locks;

  // Insure that the timeout is at least 1 ms.
  $timeout = max($timeout, 0.001);
  $expire = microtime(TRUE) + $timeout;
  if (isset($locks[$name])) {
    // Try to extend the expiration of a lock we already acquired.
    $success = (bool) db_update('semaphore')
      ->fields(array('expire' => $expire))
      ->condition('name', $name)
      ->condition('value', _lock_id())
      ->execute();
    if (!$success) {
      // The lock was broken.
      unset($locks[$name]);
    }
    return $success;
  }
  else {
    // Optimistically try to acquire the lock, then retry once if it fails.
    // The first time through the loop cannot be a retry.
    $retry = FALSE;
    // We always want to do this code at least once.
    do {
      try {
        db_insert('semaphore')
          ->fields(array(
            'name' => $name,
            'value' => _lock_id(),
            'expire' => $expire,
          ))
          ->execute();
        // We track all acquired locks in the global variable.
        $locks[$name] = TRUE;
        // We never need to try again.
        $retry = FALSE;
      }
      catch (PDOException $e) {
        // Suppress the error. If this is our first pass through the loop,
        // then $retry is FALSE. In this case, the insert must have failed
        // meaning some other request acquired the lock but did not release it.
        // We decide whether to retry by checking lock_may_be_available()
        // Since this will break the lock in case it is expired.
        $retry = $retry ? FALSE : lock_may_be_available($name);
      }
      // We only retry in case the first attempt failed, but we then broke
      // an expired lock.
    } while ($retry);
  }
  return isset($locks[$name]);
}

/**
 * Check if lock acquired by a different process may be available.
 *
 * If an existing lock has expired, it is removed.
 *
 * @param $name
 *   The name of the lock.
 *
 * @return
 *   TRUE if there is no lock or it was removed, FALSE otherwise.
 */
function lock_may_be_available($name) {
  $lock = db_query('SELECT expire, value FROM {semaphore} WHERE name = :name', array(':name' => $name))->fetchAssoc();
  if (!$lock) {
    return TRUE;
  }
  $expire = (float) $lock['expire'];
  $now = microtime(TRUE);
  if ($now > $expire) {
    // We check two conditions to prevent a race condition where another
    // request acquired the lock and set a new expire time. We add a small
    // number to $expire to avoid errors with float to string conversion.
    return (bool) db_delete('semaphore')
      ->condition('name', $name)
      ->condition('value', $lock['value'])
      ->condition('expire', 0.0001 + $expire, '<=')
      ->execute();
  }
  return FALSE;
}

/**
 * Wait for a lock to be available.
 *
 * This function may be called in a request that fails to acquire a desired
 * lock. This will block further execution until the lock is available or the
 * specified delay in seconds is reached. This should not be used with locks
 * that are acquired very frequently, since the lock is likely to be acquired
 * again by a different request during the sleep().
 *
 * @param $name
 *   The name of the lock.
 * @param $delay
 *   The maximum number of seconds to wait, as an integer.
 *
 * @return
 *   TRUE if the lock holds, FALSE if it is available.
 */
function lock_wait($name, $delay = 30) {
  $delay = (int) $delay;
  while ($delay--) {
    // This function should only be called by a request that failed to get a
    // lock, so we sleep first to give the parallel request a chance to finish
    // and release the lock.
    sleep(1);
    if (lock_may_be_available($name)) {
      // No longer need to wait.
      return FALSE;
    }
  }
  // The caller must still wait longer to get the lock.
  return TRUE;
}

/**
 * Release a lock previously acquired by lock_acquire().
 *
 * This will release the named lock if it is still held by the current request.
 *
 * @param $name
 *   The name of the lock.
 */
function lock_release($name) {
  global $locks;

  unset($locks[$name]);
  db_delete('semaphore')
    ->condition('name', $name)
    ->condition('value', _lock_id())
    ->execute();
}

/**
 * Release all previously acquired locks.
 */
function lock_release_all($lock_id = NULL) {
  global $locks;

  $locks = array();
  if (empty($lock_id)) {
    $lock_id = _lock_id();
  }
  db_delete('semaphore')
    ->condition('value', $lock_id)
    ->execute();
}

/**
 * @} End of "defgroup lock".
 */

<?php

/**
 * @file
 * Contains \Drupal\Core\Lock\LockBackendInterface.
 */

namespace Drupal\Core\Lock;

/**
 * @defgroup lock Locking mechanisms
 * @{
 * Functions to coordinate long-running operations across requests.
 *
 * In most environments, multiple Drupal page requests (a.k.a. threads or
 * processes) will execute in parallel. This leads to potential conflicts or
 * race conditions when two requests execute the same code at the same time. For
 * instance, some implementations of hook_cron() implicitly assume they are
 * running only once, rather than having multiple calls in parallel. To prevent
 * problems with such code, the cron system uses a locking process to ensure
 * that cron is not started again if it is already running.
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
 *   $lock = \Drupal::lock();
 *   if ($lock->acquire('mymodule_long_operation')) {
 *     // Do the long operation here.
 *     // ...
 *     $lock->release('mymodule_long_operation');
 *   }
 * }
 * @endcode
 *
 * If a function acquires a lock it should always release it when the operation
 * is complete by calling $lock->release(), as in the example.
 *
 * A function that has acquired a lock may attempt to renew a lock (extend the
 * duration of the lock) by calling $lock->acquire() again during the operation.
 * Failure to renew a lock is indicative that another request has acquired the
 * lock, and that the current operation may need to be aborted.
 *
 * If a function fails to acquire a lock it may either immediately return, or
 * it may call $lock->wait() if the rest of the current page request requires
 * that the operation in question be complete. After $lock->wait() returns, the
 * function may again attempt to acquire the lock, or may simply allow the page
 * request to proceed on the assumption that a parallel request completed the
 * operation.
 *
 * $lock->acquire() and $lock->wait() will automatically break (delete) a lock
 * whose duration has exceeded the timeout specified when it was acquired.
 *
 * @} End of "defgroup lock".
 */

/**
 * Lock backend interface.
 *
 * @ingroup lock
 */
interface LockBackendInterface {

  /**
   * Acquires a lock.
   *
   * @param string $name
   *   Lock name. Limit of name's length is 255 characters.
   * @param float $timeout = 30.0
   *   (optional) Lock lifetime in seconds.
   *
   * @return bool
   */
  public function acquire($name, $timeout = 30.0);

  /**
   * Checks if a lock is available for acquiring.
   *
   * @param string $name
   *   Lock to acquire.
   *
   * @return bool
   */
  public function lockMayBeAvailable($name);

  /**
   * Waits a short amount of time before a second lock acquire attempt.
   *
   * While this method is subject to have a generic implementation in abstract
   * backend implementation, some backends may provide non blocking or less I/O
   * intensive wait mechanism: this is why this method remains on the backend
   * interface.
   *
   * @param string $name
   *   Lock name currently being locked.
   * @param int $delay = 30
   *   Milliseconds to wait for.
   *
   * @return bool
   *   TRUE if the lock holds, FALSE if it may be available. You still need to
   *   acquire the lock manually and it may fail again.
   */
  public function wait($name, $delay = 30);

  /**
   * Releases the given lock.
   *
   * @param string $name
   */
  public function release($name);

  /**
   * Releases all locks for the given lock token identifier.
   *
   * @param string $lockId
   *   (optional) If none given, remove all locks from the current page.
   *   Defaults to NULL.
   */
  public function releaseAll($lockId = NULL);

  /**
   * Gets the unique page token for locks.
   *
   * Locks will be wiped out at the end of each page request on a token basis.
   *
   * @return string
   */
  public function getLockId();
}

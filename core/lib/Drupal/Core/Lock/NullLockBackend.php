<?php

/**
 * @file
 * Definition of Drupal\Core\Lock\NullLockBackend.
 */

namespace Drupal\Core\Lock;

/**
 * Defines a Null lock backend.
 *
 * This implementation won't actually lock anything and will always succeed on
 * lock attempts.
 *
 * @ingroup lock
 */
class NullLockBackend implements LockBackendInterface {

  /**
   * Current page lock token identifier.
   *
   * @var string
   */
  protected $lockId;

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::acquire().
   */
  public function acquire($name, $timeout = 30.0) {
    return TRUE;
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::lockMayBeAvailable().
   */
  public function lockMayBeAvailable($name) {
    return TRUE;
  }

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::wait().
   */
  public function wait($name, $delay = 30) {}

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::release().
   */
  public function release($name) {}

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::releaseAll().
   */
  public function releaseAll($lock_id = NULL) {}

  /**
   * Implements Drupal\Core\Lock\LockBackedInterface::getLockId().
   */
  public function getLockId() {
    if (!isset($this->lockId)) {
      $this->lockId = uniqid(mt_rand(), TRUE);
    }
    return $this->lockId;
  }
}

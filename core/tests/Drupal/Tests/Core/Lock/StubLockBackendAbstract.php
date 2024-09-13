<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Lock;

use Drupal\Core\Lock\LockBackendAbstract;

/**
 * A stub of the abstract LockBackendAbstract class for testing purposes.
 */
class StubLockBackendAbstract extends LockBackendAbstract {

  /**
   * {@inheritdoc}
   */
  public function acquire($name, $timeout = 30.0): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function lockMayBeAvailable($name): bool {
    throw new \LogicException(__METHOD__ . '() is not implemented.');
  }

  /**
   * {@inheritdoc}
   */
  public function release($name): void {
  }

  /**
   * {@inheritdoc}
   */
  public function releaseAll($lockId = NULL): void {
  }

}

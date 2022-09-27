<?php

namespace Drupal\KernelTests\Core\Lock;

use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Database lock backend.
 *
 * @group Lock
 */
class LockTest extends KernelTestBase {

  /**
   * Database lock backend to test.
   *
   * @var \Drupal\Core\Lock\DatabaseLockBackend
   */
  protected $lock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->lock = new DatabaseLockBackend($this->container->get('database'));
  }

  /**
   * Tests backend release functionality.
   */
  public function testBackendLockRelease() {
    $success = $this->lock->acquire('lock_a');
    $this->assertTrue($success, 'Could acquire first lock.');

    // This function is not part of the backend, but the default database
    // backend implement it, we can here use it safely.
    $is_free = $this->lock->lockMayBeAvailable('lock_a');
    $this->assertFalse($is_free, 'First lock is unavailable.');

    $this->lock->release('lock_a');
    $is_free = $this->lock->lockMayBeAvailable('lock_a');
    $this->assertTrue($is_free, 'First lock has been released.');

    $success = $this->lock->acquire('lock_b');
    $this->assertTrue($success, 'Could acquire second lock.');

    $success = $this->lock->acquire('lock_b');
    $this->assertTrue($success, 'Could acquire second lock a second time within the same request.');

    $this->lock->release('lock_b');

    // Test acquiring and releasing a lock with a long key (over 255 chars).
    $long_key = 'long_key:BZoMiSf9IIPULsJ98po18TxJ6T4usd3MZrLE0d3qMgG6iAgDlOi1G3oMap7zI5df84l7LtJBg4bOj6XvpO6vDRmP5h5QbA0Bj9rVFiPIPAIQZ9qFvJqTALiK1OR3GpOkWQ4vgEA4LkY0UfznrWBeuK7IWZfv1um6DLosnVXd1z1cJjvbEUqYGJj92rwHfhYihLm8IO9t3P2gAvEkH5Mhc8GBoiTsIDnP01Te1kxGFHO3RuvJIxPnHmZtSdBggmuVN7x9';

    $success = $this->lock->acquire($long_key);
    $this->assertTrue($success, 'Could acquire long key lock.');

    // This function is not part of the backend, but the default database
    // backend implement it, we can here use it safely.
    $is_free = $this->lock->lockMayBeAvailable($long_key);
    $this->assertFalse($is_free, 'Long key lock is unavailable.');

    $this->lock->release($long_key);
    $is_free = $this->lock->lockMayBeAvailable($long_key);
    $this->assertTrue($is_free, 'Long key lock has been released.');
  }

  /**
   * Tests backend release functionality.
   */
  public function testBackendLockReleaseAll() {
    $success = $this->lock->acquire('lock_a');
    $this->assertTrue($success, 'Could acquire first lock.');

    $success = $this->lock->acquire('lock_b');
    $this->assertTrue($success, 'Could acquire second lock.');

    $this->lock->releaseAll();

    $is_free = $this->lock->lockMayBeAvailable('lock_a');
    $this->assertTrue($is_free, 'First lock has been released.');

    $is_free = $this->lock->lockMayBeAvailable('lock_b');
    $this->assertTrue($is_free, 'Second lock has been released.');
  }

}

<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\LockFunctionalTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the lock system.
 */
class LockFunctionalTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Locking framework tests',
      'description' => 'Confirm locking works between two separate requests.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp('system_test');
  }

  /**
   * Tests backend release functionality.
   */
  public function testBackendLockRelease() {
    $backend = lock();

    $success = $backend->acquire('lock_a');
    $this->assertTrue($success, "Could acquire first lock");

    // This function is not part of the backend, but the default database
    // backend implement it, we can here use it safely.
    $is_free = $backend->lockMayBeAvailable('lock_a');
    $this->assertFalse($is_free, "First lock is unavailable");

    $backend->release('lock_a');
    $is_free = $backend->lockMayBeAvailable('lock_a');
    $this->assertTrue($is_free, "First lock has been released");

    $success = $backend->acquire('lock_b');
    $this->assertTrue($success, "Could acquire second lock");

    $success = $backend->acquire('lock_b');
    $this->assertTrue($success, "Could acquire second lock a second time within the same request");

    $backend->release('lock_b');
  }

  /**
   * Tests backend release functionality.
   */
  public function testBackendLockReleaseAll() {
    $backend = lock();

    $success = $backend->acquire('lock_a');
    $this->assertTrue($success, "Could acquire first lock");

    $success = $backend->acquire('lock_b');
    $this->assertTrue($success, "Could acquire second lock");

    $backend->releaseAll();

    $is_free = $backend->lockMayBeAvailable('lock_a');
    $this->assertTrue($is_free, "First lock has been released");

    $is_free = $backend->lockMayBeAvailable('lock_b');
    $this->assertTrue($is_free, "Second lock has been released");
  }

  /**
   * Confirms that we can acquire and release locks in two parallel requests.
   */
  public function testLockAcquire() {
    $lock_acquired = 'TRUE: Lock successfully acquired in system_test_lock_acquire()';
    $lock_not_acquired = 'FALSE: Lock not acquired in system_test_lock_acquire()';
    $this->assertTrue(lock_acquire('system_test_lock_acquire'), t('Lock acquired by this request.'), t('Lock'));
    $this->assertTrue(lock_acquire('system_test_lock_acquire'), t('Lock extended by this request.'), t('Lock'));
    lock_release('system_test_lock_acquire');

    // Cause another request to acquire the lock.
    $this->drupalGet('system-test/lock-acquire');
    $this->assertText($lock_acquired, t('Lock acquired by the other request.'), t('Lock'));
    // The other request has finished, thus it should have released its lock.
    $this->assertTrue(lock_acquire('system_test_lock_acquire'), t('Lock acquired by this request.'), t('Lock'));
    // This request holds the lock, so the other request cannot acquire it.
    $this->drupalGet('system-test/lock-acquire');
    $this->assertText($lock_not_acquired, t('Lock not acquired by the other request.'), t('Lock'));
    lock_release('system_test_lock_acquire');

    // Try a very short timeout and lock breaking.
    $this->assertTrue(lock_acquire('system_test_lock_acquire', 0.5), t('Lock acquired by this request.'), t('Lock'));
    sleep(1);
    // The other request should break our lock.
    $this->drupalGet('system-test/lock-acquire');
    $this->assertText($lock_acquired, t('Lock acquired by the other request, breaking our lock.'), t('Lock'));
    // We cannot renew it, since the other thread took it.
    $this->assertFalse(lock_acquire('system_test_lock_acquire'), t('Lock cannot be extended by this request.'), t('Lock'));

    // Check the shut-down function.
    $lock_acquired_exit = 'TRUE: Lock successfully acquired in system_test_lock_exit()';
    $lock_not_acquired_exit = 'FALSE: Lock not acquired in system_test_lock_exit()';
    $this->drupalGet('system-test/lock-exit');
    $this->assertText($lock_acquired_exit, t('Lock acquired by the other request before exit.'), t('Lock'));
    $this->assertTrue(lock_acquire('system_test_lock_exit'), t('Lock acquired by this request after the other request exits.'), t('Lock'));
  }
}

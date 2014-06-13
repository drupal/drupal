<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Lock\LockFunctionalTest.
 */

namespace Drupal\system\Tests\Lock;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the lock system.
 */
class LockFunctionalTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system_test');

  public static function getInfo() {
    return array(
      'name' => 'Locking framework tests',
      'description' => 'Confirm locking works between two separate requests.',
      'group' => 'Lock',
    );
  }

  /**
   * Confirms that we can acquire and release locks in two parallel requests.
   */
  public function testLockAcquire() {
    $lock = $this->container->get('lock');
    $lock_acquired = 'TRUE: Lock successfully acquired in system_test_lock_acquire()';
    $lock_not_acquired = 'FALSE: Lock not acquired in system_test_lock_acquire()';
    $this->assertTrue($lock->acquire('system_test_lock_acquire'), 'Lock acquired by this request.', 'Lock');
    $this->assertTrue($lock->acquire('system_test_lock_acquire'), 'Lock extended by this request.', 'Lock');
    $lock->release('system_test_lock_acquire');

    // Cause another request to acquire the lock.
    $this->drupalGet('system-test/lock-acquire');
    $this->assertText($lock_acquired, 'Lock acquired by the other request.', 'Lock');
    // The other request has finished, thus it should have released its lock.
    $this->assertTrue($lock->acquire('system_test_lock_acquire'), 'Lock acquired by this request.', 'Lock');
    // This request holds the lock, so the other request cannot acquire it.
    $this->drupalGet('system-test/lock-acquire');
    $this->assertText($lock_not_acquired, 'Lock not acquired by the other request.', 'Lock');
    $lock->release('system_test_lock_acquire');

    // Try a very short timeout and lock breaking.
    $this->assertTrue($lock->acquire('system_test_lock_acquire', 0.5), 'Lock acquired by this request.', 'Lock');
    sleep(1);
    // The other request should break our lock.
    $this->drupalGet('system-test/lock-acquire');
    $this->assertText($lock_acquired, 'Lock acquired by the other request, breaking our lock.', 'Lock');
    // We cannot renew it, since the other thread took it.
    $this->assertFalse($lock->acquire('system_test_lock_acquire'), 'Lock cannot be extended by this request.', 'Lock');

    // Check the shut-down function.
    $lock_acquired_exit = 'TRUE: Lock successfully acquired in system_test_lock_exit()';
    $this->drupalGet('system-test/lock-exit');
    $this->assertText($lock_acquired_exit, 'Lock acquired by the other request before exit.', 'Lock');
    $this->assertTrue($lock->acquire('system_test_lock_exit'), 'Lock acquired by this request after the other request exits.', 'Lock');
  }
}

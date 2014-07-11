<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Lock\LockUnitTest.
 */

namespace Drupal\system\Tests\Lock;

use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the Database lock backend.
 *
 * @group Lock
 */
class LockUnitTest extends DrupalUnitTestBase {

  /**
   * Database lock backend to test.
   *
   * @var \Drupal\Core\Lock\DatabaseLockBackend
   */
  protected $lock;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  public function setUp() {
    parent::setUp();
    $this->lock = new DatabaseLockBackend($this->container->get('database'));
    $this->installSchema('system', 'semaphore');
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

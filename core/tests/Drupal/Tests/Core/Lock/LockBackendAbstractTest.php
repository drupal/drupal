<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Lock\LockBackendAbstractTest.
 */

namespace Drupal\Tests\Core\Lock;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Tests\Core\Lock\LockBackendAbstractTest
 * @group Lock
 */
class LockBackendAbstractTest extends UnitTestCase {

  /**
   * The Mocked LockBackendAbstract object.
   *
   * @var \Drupal\Core\Lock\LockBackendAbstract|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $lock;

  protected function setUp() {
    $this->lock = $this->getMockForAbstractClass('Drupal\Core\Lock\LockBackendAbstract');
  }

  /**
   * Tests the wait() method when lockMayBeAvailable() returns TRUE.
   */
  public function testWaitFalse() {
    $this->lock->expects($this->any())
      ->method('lockMayBeAvailable')
      ->with($this->equalTo('test_name'))
      ->will($this->returnValue(TRUE));

    $this->assertFalse($this->lock->wait('test_name'));
  }

  /**
   * Tests the wait() method when lockMayBeAvailable() returns FALSE.
   *
   * Waiting could take 1 second so we need to extend the possible runtime.
   * @medium
   */
  public function testWaitTrue() {
    $this->lock->expects($this->any())
      ->method('lockMayBeAvailable')
      ->with($this->equalTo('test_name'))
      ->will($this->returnValue(FALSE));

    $this->assertTrue($this->lock->wait('test_name', 1));
  }

  /**
   * Test the getLockId() method.
   */
  public function testGetLockId() {
    $lock_id = $this->lock->getLockId();
    $this->assertInternalType('string', $lock_id);
    // Example lock ID would be '7213141505232b6ee2cb967.27683891'.
    $this->assertRegExp('/[\da-f]+\.\d+/', $lock_id);
    // Test the same lock ID is returned a second time.
    $this->assertSame($lock_id, $this->lock->getLockId());
  }

}

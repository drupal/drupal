<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Lock;

use Drupal\Core\Lock\LockBackendAbstract;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\Tests\Core\Lock\LockBackendAbstractTest
 * @group Lock
 */
class LockBackendAbstractTest extends UnitTestCase {

  /**
   * The Mocked LockBackendAbstract object.
   */
  protected LockBackendAbstract&MockObject $lock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->lock = $this->getMockBuilder(StubLockBackendAbstract::class)
      ->onlyMethods(['lockMayBeAvailable'])
      ->getMock();
  }

  /**
   * Tests the wait() method when lockMayBeAvailable() returns TRUE.
   */
  public function testWaitFalse(): void {
    $this->lock->expects($this->any())
      ->method('lockMayBeAvailable')
      ->with($this->equalTo('test_name'))
      ->willReturn(TRUE);

    $this->assertFalse($this->lock->wait('test_name'));
  }

  /**
   * Tests the wait() method when lockMayBeAvailable() returns FALSE.
   *
   * Waiting could take 1 second so we need to extend the possible runtime.
   *
   * @medium
   */
  public function testWaitTrue(): void {
    $this->lock->expects($this->any())
      ->method('lockMayBeAvailable')
      ->with($this->equalTo('test_name'))
      ->willReturn(FALSE);

    $this->assertTrue($this->lock->wait('test_name', 1));
  }

  /**
   * Tests the getLockId() method.
   */
  public function testGetLockId(): void {
    $lock_id = $this->lock->getLockId();
    $this->assertIsString($lock_id);
    // Example lock ID would be '7213141505232b6ee2cb967.27683891'.
    $this->assertMatchesRegularExpression('/[\da-f]+\.\d+/', $lock_id);
    // Test the same lock ID is returned a second time.
    $this->assertSame($lock_id, $this->lock->getLockId());
  }

}

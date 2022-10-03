<?php

namespace Drupal\Tests\Core\TempStore;

use Drupal\Core\TempStore\Lock;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\TempStoreException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\TempStore\PrivateTempStore
 * @group TempStore
 */
class PrivateTempStoreTest extends UnitTestCase {

  /**
   * The mock key value expirable backend.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyValue;

  /**
   * The mock lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * The temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * A tempstore object belonging to the owner.
   *
   * @var object
   */
  protected $ownObject;

  /**
   * A tempstore object not belonging to the owner.
   *
   * @var object
   */
  protected $otherObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->keyValue = $this->createMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $this->lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $this->currentUser = $this->createMock('Drupal\Core\Session\AccountProxyInterface');
    $this->currentUser->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $this->requestStack = new RequestStack();
    $request = Request::createFromGlobals();
    $this->requestStack->push($request);

    $this->tempStore = new PrivateTempStore($this->keyValue, $this->lock, $this->currentUser, $this->requestStack, 604800);

    $this->ownObject = (object) [
      'data' => 'test_data',
      'owner' => $this->currentUser->id(),
      'updated' => (int) $request->server->get('REQUEST_TIME'),
    ];

    // Clone the object but change the owner.
    $this->otherObject = clone $this->ownObject;
    $this->otherObject->owner = 2;
  }

  /**
   * Tests the get() method.
   *
   * @covers ::get
   */
  public function testGet() {
    $this->keyValue->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(
        ['1:test_2'],
        ['1:test'],
        ['1:test'],
      )
      ->willReturnOnConsecutiveCalls(
        FALSE,
        $this->ownObject,
        $this->otherObject,
      );

    $this->assertNull($this->tempStore->get('test_2'));
    $this->assertSame($this->ownObject->data, $this->tempStore->get('test'));
    $this->assertNull($this->tempStore->get('test'));
  }

  /**
   * Tests the set() method with no lock available.
   *
   * @covers ::set
   */
  public function testSetWithNoLockAvailable() {
    $this->lock->expects($this->exactly(2))
      ->method('acquire')
      ->with('1:test')
      ->willReturn(FALSE);
    $this->lock->expects($this->once())
      ->method('wait')
      ->with('1:test');

    $this->keyValue->expects($this->once())
      ->method('getCollectionName');

    $this->expectException(TempStoreException::class);
    $this->tempStore->set('test', 'value');
  }

  /**
   * Tests a successful set() call.
   *
   * @covers ::set
   */
  public function testSet() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('1:test')
      ->willReturn(TRUE);
    $this->lock->expects($this->never())
      ->method('wait');
    $this->lock->expects($this->once())
      ->method('release')
      ->with('1:test');

    $this->keyValue->expects($this->once())
      ->method('setWithExpire')
      ->with('1:test', $this->ownObject, 604800);

    $this->tempStore->set('test', 'test_data');
  }

  /**
   * Tests the getMetadata() method.
   *
   * @covers ::getMetadata
   */
  public function testGetMetadata() {
    $this->keyValue->expects($this->exactly(2))
      ->method('get')
      ->with('1:test')
      ->willReturnOnConsecutiveCalls($this->ownObject, FALSE);

    $metadata = $this->tempStore->getMetadata('test');
    $this->assertInstanceOf(Lock::class, $metadata);
    $this->assertObjectHasAttribute('ownerId', $metadata);
    $this->assertObjectHasAttribute('updated', $metadata);
    // Data should get removed.
    $this->assertObjectNotHasAttribute('data', $metadata);

    $this->assertNull($this->tempStore->getMetadata('test'));
  }

  /**
   * Tests the locking in the delete() method.
   *
   * @covers ::delete
   */
  public function testDeleteLocking() {
    $this->keyValue->expects($this->once())
      ->method('get')
      ->with('1:test')
      ->willReturn($this->ownObject);
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('1:test')
      ->willReturn(TRUE);
    $this->lock->expects($this->never())
      ->method('wait');
    $this->lock->expects($this->once())
      ->method('release')
      ->with('1:test');

    $this->keyValue->expects($this->once())
      ->method('delete')
      ->with('1:test');

    $this->assertTrue($this->tempStore->delete('test'));
  }

  /**
   * Tests the delete() method with no lock available.
   *
   * @covers ::delete
   */
  public function testDeleteWithNoLockAvailable() {
    $this->keyValue->expects($this->once())
      ->method('get')
      ->with('1:test')
      ->willReturn($this->ownObject);
    $this->lock->expects($this->exactly(2))
      ->method('acquire')
      ->with('1:test')
      ->willReturn(FALSE);
    $this->lock->expects($this->once())
      ->method('wait')
      ->with('1:test');

    $this->keyValue->expects($this->once())
      ->method('getCollectionName');

    $this->expectException(TempStoreException::class);
    $this->tempStore->delete('test');
  }

  /**
   * Tests the delete() method.
   *
   * @covers ::delete
   */
  public function testDelete() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('1:test_2')
      ->willReturn(TRUE);

    $this->keyValue->expects($this->exactly(3))
      ->method('get')
      ->withConsecutive(
        ['1:test_1'],
        ['1:test_2'],
        ['1:test_3'],
      )
      ->willReturnOnConsecutiveCalls(
        FALSE,
        $this->ownObject,
        $this->otherObject,
      );
    $this->keyValue->expects($this->once())
      ->method('delete')
      ->with('1:test_2');

    $this->assertTrue($this->tempStore->delete('test_1'));
    $this->assertTrue($this->tempStore->delete('test_2'));
    $this->assertFalse($this->tempStore->delete('test_3'));
  }

}

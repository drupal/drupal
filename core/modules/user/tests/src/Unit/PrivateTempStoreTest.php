<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\PrivateTempStoreTest.
 */

namespace Drupal\Tests\user\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\user\PrivateTempStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\user\PrivateTempStore
 * @group user
 */
class PrivateTempStoreTest extends UnitTestCase {

  /**
   * The mock key value expirable backend.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $keyValue;

  /**
   * The mock lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $lock;

  /**
   * The user temp store.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit_Framework_MockObject_MockObject
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
   * @var \stdClass
   */
  protected $ownObject;

  /**
   * A tempstore object not belonging to the owner.
   *
   * @var \stdClass
   */
  protected $otherObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->keyValue = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountProxyInterface');
    $this->currentUser->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $this->requestStack = new RequestStack();
    $request = Request::createFromGlobals();
    $this->requestStack->push($request);

    $this->tempStore = new PrivateTempStore($this->keyValue, $this->lock, $this->currentUser, $this->requestStack, 604800);

    $this->ownObject = (object) array(
      'data' => 'test_data',
      'owner' => $this->currentUser->id(),
      'updated' => (int) $request->server->get('REQUEST_TIME'),
    );

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
    $this->keyValue->expects($this->at(0))
      ->method('get')
      ->with('1:test_2')
      ->will($this->returnValue(FALSE));
    $this->keyValue->expects($this->at(1))
      ->method('get')
      ->with('1:test')
      ->will($this->returnValue($this->ownObject));
    $this->keyValue->expects($this->at(2))
      ->method('get')
      ->with('1:test')
      ->will($this->returnValue($this->otherObject));

    $this->assertNull($this->tempStore->get('test_2'));
    $this->assertSame($this->ownObject->data, $this->tempStore->get('test'));
    $this->assertNull($this->tempStore->get('test'));
  }

  /**
   * Tests the set() method with no lock available.
   *
   * @covers ::set
   * @expectedException \Drupal\user\TempStoreException
   */
  public function testSetWithNoLockAvailable() {
    $this->lock->expects($this->at(0))
      ->method('acquire')
      ->with('1:test')
      ->will($this->returnValue(FALSE));
    $this->lock->expects($this->at(1))
      ->method('wait')
      ->with('1:test');
    $this->lock->expects($this->at(2))
      ->method('acquire')
      ->with('1:test')
      ->will($this->returnValue(FALSE));

    $this->keyValue->expects($this->once())
      ->method('getCollectionName');

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
      ->will($this->returnValue(TRUE));
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
    $this->keyValue->expects($this->at(0))
      ->method('get')
      ->with('1:test')
      ->will($this->returnValue($this->ownObject));

    $this->keyValue->expects($this->at(1))
      ->method('get')
      ->with('1:test')
      ->will($this->returnValue(FALSE));

    $metadata = $this->tempStore->getMetadata('test');
    $this->assertObjectHasAttribute('owner', $metadata);
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
      ->will($this->returnValue($this->ownObject));
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('1:test')
      ->will($this->returnValue(TRUE));
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
   * @expectedException \Drupal\user\TempStoreException
   */
  public function testDeleteWithNoLockAvailable() {
    $this->keyValue->expects($this->once())
      ->method('get')
      ->with('1:test')
      ->will($this->returnValue($this->ownObject));
    $this->lock->expects($this->at(0))
      ->method('acquire')
      ->with('1:test')
      ->will($this->returnValue(FALSE));
    $this->lock->expects($this->at(1))
      ->method('wait')
      ->with('1:test');
    $this->lock->expects($this->at(2))
      ->method('acquire')
      ->with('1:test')
      ->will($this->returnValue(FALSE));

    $this->keyValue->expects($this->once())
      ->method('getCollectionName');

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
      ->will($this->returnValue(TRUE));

    $this->keyValue->expects($this->at(0))
      ->method('get')
      ->with('1:test_1')
      ->will($this->returnValue(FALSE));
    $this->keyValue->expects($this->at(1))
      ->method('get')
      ->with('1:test_2')
      ->will($this->returnValue($this->ownObject));
    $this->keyValue->expects($this->at(2))
      ->method('delete')
      ->with('1:test_2');
    $this->keyValue->expects($this->at(3))
      ->method('get')
      ->with('1:test_3')
      ->will($this->returnValue($this->otherObject));

    $this->assertTrue($this->tempStore->delete('test_1'));
    $this->assertTrue($this->tempStore->delete('test_2'));
    $this->assertFalse($this->tempStore->delete('test_3'));
  }

}


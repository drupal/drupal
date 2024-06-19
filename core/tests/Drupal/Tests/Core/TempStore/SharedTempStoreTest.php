<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\TempStore;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\Lock;
use Drupal\Core\Test\TestKernel;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\TempStoreException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @coversDefaultClass \Drupal\Core\TempStore\SharedTempStore
 * @group TempStore
 */
class SharedTempStoreTest extends UnitTestCase {

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
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The owner used in this test.
   *
   * @var int
   */
  protected $owner = 1;

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
    $this->requestStack = new RequestStack();
    $request = Request::createFromGlobals();
    $session = $this->createMock(SessionInterface::class);
    $request->setSession($session);
    $this->requestStack->push($request);
    $current_user = $this->createMock(AccountProxyInterface::class);

    $this->tempStore = new SharedTempStore($this->keyValue, $this->lock, $this->owner, $this->requestStack, $current_user, 604800);

    $this->ownObject = (object) [
      'data' => 'test_data',
      'owner' => $this->owner,
      'updated' => (int) $request->server->get('REQUEST_TIME'),
    ];

    // Clone the object but change the owner.
    $this->otherObject = clone $this->ownObject;
    $this->otherObject->owner = 2;
  }

  /**
   * @covers ::get
   */
  public function testGet(): void {
    $calls = ['test_2', 'test'];
    $this->keyValue->expects($this->exactly(count($calls)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$calls): bool {
        return array_shift($calls) == $key;
      }))
      ->willReturnOnConsecutiveCalls(
        FALSE,
        $this->ownObject,
      );

    $this->assertNull($this->tempStore->get('test_2'));
    $this->assertSame($this->ownObject->data, $this->tempStore->get('test'));
  }

  /**
   * Tests the getIfOwner() method.
   *
   * @covers ::getIfOwner
   */
  public function testGetIfOwner(): void {
    $calls = ['test_2', 'test', 'test'];
    $this->keyValue->expects($this->exactly(count($calls)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$calls): bool {
        return array_shift($calls) == $key;
      }))
      ->willReturnOnConsecutiveCalls(
        FALSE,
        $this->ownObject,
        $this->otherObject,
      );

    $this->assertNull($this->tempStore->getIfOwner('test_2'));
    $this->assertSame($this->ownObject->data, $this->tempStore->getIfOwner('test'));
    $this->assertNull($this->tempStore->getIfOwner('test'));
  }

  /**
   * Tests the set() method with no lock available.
   *
   * @covers ::set
   */
  public function testSetWithNoLockAvailable(): void {
    $this->lock->expects($this->exactly(2))
      ->method('acquire')
      ->with('test')
      ->willReturn(FALSE);
    $this->lock->expects($this->once())
      ->method('wait')
      ->with('test');

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
  public function testSet(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('test')
      ->willReturn(TRUE);
    $this->lock->expects($this->never())
      ->method('wait');
    $this->lock->expects($this->once())
      ->method('release')
      ->with('test');

    $this->keyValue->expects($this->once())
      ->method('setWithExpire')
      ->with('test', $this->ownObject, 604800);

    $this->tempStore->set('test', 'test_data');
  }

  /**
   * Tests the setIfNotExists() methods.
   *
   * @covers ::setIfNotExists
   */
  public function testSetIfNotExists(): void {
    $this->keyValue->expects($this->once())
      ->method('setWithExpireIfNotExists')
      ->with('test', $this->ownObject, 604800)
      ->willReturn(TRUE);

    $this->assertTrue($this->tempStore->setIfNotExists('test', 'test_data'));
  }

  /**
   * Tests the setIfOwner() method when no key exists.
   *
   * @covers ::setIfOwner
   */
  public function testSetIfOwnerWhenNotExists(): void {
    $this->keyValue->expects($this->once())
      ->method('setWithExpireIfNotExists')
      ->willReturn(TRUE);

    $this->assertTrue($this->tempStore->setIfOwner('test', 'test_data'));
  }

  /**
   * Tests the setIfOwner() method when a key already exists but no object.
   *
   * @covers ::setIfOwner
   */
  public function testSetIfOwnerNoObject(): void {
    $this->keyValue->expects($this->once())
      ->method('setWithExpireIfNotExists')
      ->willReturn(FALSE);

    $this->keyValue->expects($this->once())
      ->method('get')
      ->with('test')
      ->willReturn(FALSE);

    $this->assertFalse($this->tempStore->setIfOwner('test', 'test_data'));
  }

  /**
   * Tests the setIfOwner() method with matching and non matching owners.
   *
   * @covers ::setIfOwner
   */
  public function testSetIfOwner(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('test')
      ->willReturn(TRUE);

    $this->keyValue->expects($this->exactly(2))
      ->method('setWithExpireIfNotExists')
      ->willReturn(FALSE);

    $this->keyValue->expects($this->exactly(2))
      ->method('get')
      ->with('test')
      ->willReturn($this->ownObject, $this->otherObject);

    $this->assertTrue($this->tempStore->setIfOwner('test', 'test_data'));
    $this->assertFalse($this->tempStore->setIfOwner('test', 'test_data'));
  }

  /**
   * Tests the getMetadata() method.
   *
   * @covers ::getMetadata
   */
  public function testGetMetadata(): void {
    $this->keyValue->expects($this->exactly(2))
      ->method('get')
      ->with('test')
      ->willReturnOnConsecutiveCalls($this->ownObject, FALSE);

    $metadata = $this->tempStore->getMetadata('test');
    $this->assertInstanceOf(Lock::class, $metadata);
    $this->assertObjectHasProperty('updated', $metadata);
    // Data should get removed.
    $this->assertObjectNotHasProperty('data', $metadata);

    $this->assertNull($this->tempStore->getMetadata('test'));
  }

  /**
   * Tests the delete() method.
   *
   * @covers ::delete
   */
  public function testDelete(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('test')
      ->willReturn(TRUE);
    $this->lock->expects($this->never())
      ->method('wait');
    $this->lock->expects($this->once())
      ->method('release')
      ->with('test');

    $this->keyValue->expects($this->once())
      ->method('delete')
      ->with('test');

    $this->tempStore->delete('test');
  }

  /**
   * Tests the delete() method with no lock available.
   *
   * @covers ::delete
   */
  public function testDeleteWithNoLockAvailable(): void {
    $this->lock->expects($this->exactly(2))
      ->method('acquire')
      ->with('test')
      ->willReturn(FALSE);
    $this->lock->expects($this->once())
      ->method('wait')
      ->with('test');

    $this->keyValue->expects($this->once())
      ->method('getCollectionName');

    $this->expectException(TempStoreException::class);
    $this->tempStore->delete('test');
  }

  /**
   * Tests the deleteIfOwner() method.
   *
   * @covers ::deleteIfOwner
   */
  public function testDeleteIfOwner(): void {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('test_2')
      ->willReturn(TRUE);

    $calls = ['test_1', 'test_2', 'test_3'];
    $this->keyValue->expects($this->exactly(count($calls)))
      ->method('get')
      ->with($this->callback(function (string $key) use (&$calls): bool {
        return array_shift($calls) == $key;
      }))
      ->willReturnOnConsecutiveCalls(
        FALSE,
        $this->ownObject,
        $this->otherObject,
      );
    $this->keyValue->expects($this->once())
      ->method('delete')
      ->with('test_2');

    $this->assertTrue($this->tempStore->deleteIfOwner('test_1'));
    $this->assertTrue($this->tempStore->deleteIfOwner('test_2'));
    $this->assertFalse($this->tempStore->deleteIfOwner('test_3'));
  }

  /**
   * Tests the serialization of a shared temp store.
   */
  public function testSerialization(): void {
    // Add an unserializable request to the request stack. If the tempstore
    // didn't use DependencySerializationTrait, an exception would be thrown
    // when we try to serialize the tempstore.
    $unserializable_request = new UnserializableRequest();

    $this->requestStack->push($unserializable_request);

    $container = TestKernel::setContainerWithKernel();
    $container->set('request_stack', $this->requestStack);
    \Drupal::setContainer($container);

    $store = unserialize(serialize($this->tempStore));
    $this->assertInstanceOf(SharedTempStore::class, $store);

    $reflected_request_stack = (new \ReflectionObject($store))->getProperty('requestStack');
    $request_stack = $reflected_request_stack->getValue($store);
    $this->assertEquals($this->requestStack, $request_stack);
    $this->assertSame($unserializable_request, $request_stack->pop());
  }

}

/**
 * A class for testing.
 */
class UnserializableRequest extends Request {

  /**
   * Always throw an exception.
   */
  public function __serialize() {
    throw new \LogicException('Oops!');
  }

}

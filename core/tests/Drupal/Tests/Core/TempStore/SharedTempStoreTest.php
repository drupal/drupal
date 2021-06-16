<?php

namespace Drupal\Tests\Core\TempStore;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\Lock;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\TempStoreException;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
  public function testGet() {
    $this->keyValue->expects($this->at(0))
      ->method('get')
      ->with('test_2')
      ->will($this->returnValue(FALSE));
    $this->keyValue->expects($this->at(1))
      ->method('get')
      ->with('test')
      ->will($this->returnValue($this->ownObject));

    $this->assertNull($this->tempStore->get('test_2'));
    $this->assertSame($this->ownObject->data, $this->tempStore->get('test'));
  }

  /**
   * Tests the getIfOwner() method.
   *
   * @covers ::getIfOwner
   */
  public function testGetIfOwner() {
    $this->keyValue->expects($this->at(0))
      ->method('get')
      ->with('test_2')
      ->will($this->returnValue(FALSE));
    $this->keyValue->expects($this->at(1))
      ->method('get')
      ->with('test')
      ->will($this->returnValue($this->ownObject));
    $this->keyValue->expects($this->at(2))
      ->method('get')
      ->with('test')
      ->will($this->returnValue($this->otherObject));

    $this->assertNull($this->tempStore->getIfOwner('test_2'));
    $this->assertSame($this->ownObject->data, $this->tempStore->getIfOwner('test'));
    $this->assertNull($this->tempStore->getIfOwner('test'));
  }

  /**
   * Tests the set() method with no lock available.
   *
   * @covers ::set
   */
  public function testSetWithNoLockAvailable() {
    $this->lock->expects($this->at(0))
      ->method('acquire')
      ->with('test')
      ->will($this->returnValue(FALSE));
    $this->lock->expects($this->at(1))
      ->method('wait')
      ->with('test');
    $this->lock->expects($this->at(2))
      ->method('acquire')
      ->with('test')
      ->will($this->returnValue(FALSE));

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
      ->with('test')
      ->will($this->returnValue(TRUE));
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
  public function testSetIfNotExists() {
    $this->keyValue->expects($this->once())
      ->method('setWithExpireIfNotExists')
      ->with('test', $this->ownObject, 604800)
      ->will($this->returnValue(TRUE));

    $this->assertTrue($this->tempStore->setIfNotExists('test', 'test_data'));
  }

  /**
   * Tests the setIfOwner() method when no key exists.
   *
   * @covers ::setIfOwner
   */
  public function testSetIfOwnerWhenNotExists() {
    $this->keyValue->expects($this->once())
      ->method('setWithExpireIfNotExists')
      ->will($this->returnValue(TRUE));

    $this->assertTrue($this->tempStore->setIfOwner('test', 'test_data'));
  }

  /**
   * Tests the setIfOwner() method when a key already exists but no object.
   *
   * @covers ::setIfOwner
   */
  public function testSetIfOwnerNoObject() {
    $this->keyValue->expects($this->once())
      ->method('setWithExpireIfNotExists')
      ->will($this->returnValue(FALSE));

    $this->keyValue->expects($this->once())
      ->method('get')
      ->with('test')
      ->will($this->returnValue(FALSE));

    $this->assertFalse($this->tempStore->setIfOwner('test', 'test_data'));
  }

  /**
   * Tests the setIfOwner() method with matching and non matching owners.
   *
   * @covers ::setIfOwner
   */
  public function testSetIfOwner() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('test')
      ->will($this->returnValue(TRUE));

    $this->keyValue->expects($this->exactly(2))
      ->method('setWithExpireIfNotExists')
      ->will($this->returnValue(FALSE));

    $this->keyValue->expects($this->exactly(2))
      ->method('get')
      ->with('test')
      ->will($this->onConsecutiveCalls($this->ownObject, $this->otherObject));

    $this->assertTrue($this->tempStore->setIfOwner('test', 'test_data'));
    $this->assertFalse($this->tempStore->setIfOwner('test', 'test_data'));
  }

  /**
   * Tests the getMetadata() method.
   *
   * @covers ::getMetadata
   */
  public function testGetMetadata() {
    $this->keyValue->expects($this->at(0))
      ->method('get')
      ->with('test')
      ->will($this->returnValue($this->ownObject));

    $this->keyValue->expects($this->at(1))
      ->method('get')
      ->with('test')
      ->will($this->returnValue(FALSE));

    $metadata = $this->tempStore->getMetadata('test');
    $this->assertInstanceOf(Lock::class, $metadata);
    $this->assertObjectHasAttribute('updated', $metadata);
    // Data should get removed.
    $this->assertObjectNotHasAttribute('data', $metadata);

    $this->assertNull($this->tempStore->getMetadata('test'));
  }

  /**
   * Tests the delete() method.
   *
   * @covers ::delete
   */
  public function testDelete() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('test')
      ->will($this->returnValue(TRUE));
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
  public function testDeleteWithNoLockAvailable() {
    $this->lock->expects($this->at(0))
      ->method('acquire')
      ->with('test')
      ->will($this->returnValue(FALSE));
    $this->lock->expects($this->at(1))
      ->method('wait')
      ->with('test');
    $this->lock->expects($this->at(2))
      ->method('acquire')
      ->with('test')
      ->will($this->returnValue(FALSE));

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
  public function testDeleteIfOwner() {
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with('test_2')
      ->will($this->returnValue(TRUE));

    $this->keyValue->expects($this->at(0))
      ->method('get')
      ->with('test_1')
      ->will($this->returnValue(FALSE));
    $this->keyValue->expects($this->at(1))
      ->method('get')
      ->with('test_2')
      ->will($this->returnValue($this->ownObject));
    $this->keyValue->expects($this->at(2))
      ->method('delete')
      ->with('test_2');
    $this->keyValue->expects($this->at(3))
      ->method('get')
      ->with('test_3')
      ->will($this->returnValue($this->otherObject));

    $this->assertTrue($this->tempStore->deleteIfOwner('test_1'));
    $this->assertTrue($this->tempStore->deleteIfOwner('test_2'));
    $this->assertFalse($this->tempStore->deleteIfOwner('test_3'));
  }

  /**
   * Tests the serialization of a shared temp store.
   */
  public function testSerialization() {
    // Add an unserializable request to the request stack. If the tempstore
    // didn't use DependencySerializationTrait, the exception would be thrown
    // when we try to serialize the tempstore.
    $request = $this->prophesize(Request::class);
    $request->willImplement('\Serializable');
    $request->serialize()->willThrow(new \LogicException('Oops!'));
    $unserializable_request = $request->reveal();

    $this->requestStack->push($unserializable_request);
    $this->requestStack->_serviceId = 'request_stack';

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('request_stack')->willReturn($this->requestStack);
    $container->has('request_stack')->willReturn(TRUE);
    \Drupal::setContainer($container->reveal());

    $store = unserialize(serialize($this->tempStore));
    $this->assertInstanceOf(SharedTempStore::class, $store);

    $reflected_request_stack = (new \ReflectionObject($store))->getProperty('requestStack');
    $reflected_request_stack->setAccessible(TRUE);
    $request_stack = $reflected_request_stack->getValue($store);
    $this->assertEquals($this->requestStack, $request_stack);
    $this->assertSame($unserializable_request, $request_stack->pop());
  }

  /**
   * @group legacy
   */
  public function testLegacyConstructor() {
    $this->expectDeprecation('Calling Drupal\Core\TempStore\SharedTempStore::__construct() without the $current_user argument is deprecated in drupal:9.2.0 and will be required in drupal:10.0.0. See https://www.drupal.org/node/3006268');

    $container = new ContainerBuilder();
    $current_user = $this->createMock(AccountProxyInterface::class);
    $container->set('current_user', $current_user);
    \Drupal::setContainer($container);
    $store = new SharedTempStore($this->keyValue, $this->lock, 2, $this->requestStack, 1000);
    $reflection_class = new \ReflectionClass(SharedTempStore::class);

    $current_user_property = $reflection_class->getProperty('currentUser');
    $current_user_property->setAccessible(TRUE);
    $this->assertSame($current_user, $current_user_property->getValue($store));

    $expire_property = $reflection_class->getProperty('expire');
    $expire_property->setAccessible(TRUE);
    $this->assertSame(1000, $expire_property->getValue($store));
  }

  /**
   * @group legacy
   * @covers \Drupal\Core\TempStore\SharedTempStoreFactory::__construct
   */
  public function testLegacyFactoryConstructor() {
    $this->expectDeprecation('Calling Drupal\Core\TempStore\SharedTempStoreFactory::__construct() without the $current_user argument is deprecated in drupal:9.2.0 and will be required in drupal:10.0.0. See https://www.drupal.org/node/3006268');

    $container = new ContainerBuilder();
    $current_user = $this->createMock(AccountProxyInterface::class);
    $container->set('current_user', $current_user);
    \Drupal::setContainer($container);
    $key_value_factory = $this->prophesize(KeyValueExpirableFactoryInterface::class);
    $store = new SharedTempStoreFactory($key_value_factory->reveal(), $this->lock, $this->requestStack, 1000);
    $reflection_class = new \ReflectionClass(SharedTempStoreFactory::class);

    $current_user_property = $reflection_class->getProperty('currentUser');
    $current_user_property->setAccessible(TRUE);
    $this->assertSame($current_user, $current_user_property->getValue($store));

    $expire_property = $reflection_class->getProperty('expire');
    $expire_property->setAccessible(TRUE);
    $this->assertSame(1000, $expire_property->getValue($store));
  }

}

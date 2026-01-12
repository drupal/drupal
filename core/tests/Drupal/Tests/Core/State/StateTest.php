<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\State;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\State;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests Drupal\Core\State\State.
 */
#[CoversClass(State::class)]
#[Group('State')]
class StateTest extends UnitTestCase {

  /**
   * The mocked key value store.
   */
  protected KeyValueStoreInterface|MockObject $keyValueStorage;

  /**
   * The tested state.
   */
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->keyValueStorage = $this->getMockBuilder(KeyValueStoreInterface::class)
      ->getMock();
    $factory = $this->getMockBuilder(KeyValueFactoryInterface::class)
      ->getMock();
    $factory->expects($this->once())
      ->method('get')
      ->with('state')
      ->willReturn($this->keyValueStorage);
    $lock = $this->getMockBuilder(LockBackendInterface::class)->getMock();
    $cache = $this->getMockBuilder(CacheBackendInterface::class)
      ->getMock();
    $this->state = new State($factory, $cache, $lock);
  }

  /**
   * Tests both get() & getMultiple() method.
   *
   * Here testing a not existing variable and set and get the default value of
   * a key.
   *
   * @legacy-covers ::get
   * @legacy-covers ::getMultiple
   */
  public function testGetEmpty(): void {
    $values = ['key1' => 'value1', 'key2' => 'value2', 'not-existing-value'];
    $this->keyValueStorage->expects($this->once())
      ->method('setMultiple')
      ->with($values);

    $this->state->setMultiple($values);

    $this->assertNull($this->state->get('not-existing-value'));
    $this->assertEquals(["not-existing-value" => NULL], $this->state->getMultiple(['not-existing-value']));

    $this->assertNull($this->state->get('not-existing'));
    $this->assertEquals(["not-existing" => NULL], $this->state->getMultiple(['not-existing']));

    $this->assertEquals('default', $this->state->get('default-value', 'default'));
    $this->assertEquals(["default-value" => NULL], $this->state->getMultiple(['default-value']));
  }

  /**
   * Tests both get() & getMultiple() method.
   *
   * Here checking the key with it proper value. It is also a helper for
   * testGetStaticCache() function.
   *
   * @legacy-covers ::get
   * @legacy-covers ::getMultiple
   */
  public function testGet(): State {
    $values = ['existing' => 'the-value', 'default-value' => 'the-value-2'];
    $this->keyValueStorage->expects($this->once())
      ->method('setMultiple')
      ->with($values);

    $this->state->setMultiple($values);

    $this->assertEquals('the-value', $this->state->get('existing'));
    $this->assertEquals('the-value-2', $this->state->get('default-value', 'default'));

    $this->assertEquals(["existing" => "the-value"], $this->state->getMultiple(['existing']));
    $this->assertEquals([
      "default-value" => "the-value-2",
      "default" => NULL,
    ], $this->state->getMultiple(['default-value', 'default']));
    return $this->state;
  }

  /**
   * Tests both get() & getMultiple() method.
   *
   * Here with the help of testGet() function, testing the key value again.
   *
   * @legacy-covers ::get
   * @legacy-covers ::getMultiple
   */
  #[Depends('testGet')]
  public function testGetStaticCache(State $state): void {
    $this->keyValueStorage->expects($this->never())
      ->method('getMultiple');

    $this->assertEquals('the-value', $state->get('existing'));
    $this->assertEquals('the-value-2', $state->get('default-value', 'default'));

    $this->assertEquals(["existing" => "the-value"], $state->getMultiple(['existing']));
    $this->assertEquals([
      "default-value" => "the-value-2",
      "default" => NULL,
    ], $state->getMultiple(['default-value', 'default']));
  }

  /**
   * Tests getMultiple() method.
   *
   * Here checking the multiple key and values. It is also a helper for
   * testGetMultipleStaticCache() function.
   */
  public function testGetMultiple(): State {
    $keys = ['key1', 'key2', 'key3'];
    $values = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
    $this->keyValueStorage->expects($this->once())
      ->method('setMultiple')
      ->with($values);

    $this->state->setMultiple($values);

    $this->assertEquals($values, $this->state->getMultiple($keys));
    return $this->state;
  }

  /**
   * Tests getMultiple() method.
   *
   * Here testing all the keys with value and without values.
   */
  public function testGetMultipleWithMissingValues(): void {
    $keys = ['key1', 'key2', 'key3'];
    $values = ['key1' => 'value1', 'key2' => NULL, 'key3' => NULL];
    $this->keyValueStorage->expects($this->once())
      ->method('setMultiple')
      ->with($values);

    $this->state->setMultiple($values);

    $this->assertEquals($values, $this->state->getMultiple($keys));
  }

  /**
   * Tests getMultiple() method.
   *
   * Here with the help of testGetMultiple() function, testing the multiple
   * key value again.
   *
   * @param \Drupal\Core\State\State $state
   *   The tested state.
   */
  #[Depends('testGetMultiple')]
  public function testGetMultipleStaticCache(State $state): void {
    $keys = ['key1', 'key2', 'key3'];
    $values = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
    $this->keyValueStorage->expects($this->never())
      ->method('getMultiple');

    $this->assertEquals($values, $state->getMultiple($keys));
  }

  /**
   * Tests getMultiple() method.
   *
   * Here testing the multiple key value pare with Partially Filled Static
   * Cache.
   */
  public function testGetMultiplePartiallyFilledStaticCache(): void {
    $keys = ['key1', 'key2', 'key3'];
    $values = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];

    $this->keyValueStorage->expects($this->once())
      ->method('setMultiple')
      ->with($values + ['key4' => 'value4']);

    $this->state->setMultiple($values + ['key4' => 'value4']);

    $new_keys = array_merge($keys, ['key4']);
    $new_values = array_merge($values, ['key4' => 'value4']);

    $this->assertEquals($values, $this->state->getMultiple($keys));
    $this->assertEquals($new_values, $this->state->getMultiple($new_keys));
  }

  /**
   * Tests set() method.
   *
   * Here we are setting the key value so those value can be used in
   * testResetCache() and testSetBeforeGet() functions.
   */
  public function testSet(): State {
    $this->keyValueStorage->expects($this->once())
      ->method('set')
      ->with('key', 'value');

    $this->state->set('key', 'value');
    return $this->state;
  }

  /**
   * Tests get() method.
   *
   * Here testing the key value right after we setting it with testSet()
   * function.
   *
   * @param \Drupal\Core\State\State $state
   *   The tested state.
   *
   * @legacy-covers ::get
   */
  #[Depends('testSet')]
  public function testSetBeforeGet(State $state): void {
    $this->assertEquals('value', $state->get('key'));
  }

  /**
   * Tests setMultiple() method.
   *
   * Here we are saving multiple key value pare in one go. Those value will be
   * used in testResetCache() and testSetBeforeGet() functions.
   */
  public function testSetMultiple(): State {
    $values = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
    $this->keyValueStorage->expects($this->once())
      ->method('setMultiple')
      ->with($values);

    $this->state->setMultiple($values);
    return $this->state;
  }

  /**
   * Tests getMultiple() method.
   *
   * Here testing the key value right after we setting it with testSetMultiple()
   * function.
   *
   * @param \Drupal\Core\State\State $state
   *   The tested state.
   *
   * @legacy-covers ::getMultiple
   */
  #[Depends('testSetMultiple')]
  public function testSetMultipleBeforeGetMultiple(State $state): void {
    $this->assertEquals([
      'key1' => 'value1',
      'key2' => 'value2',
    ], $state->getMultiple(['key1', 'key2']));
  }

  /**
   * Tests both delete() & deleteMultiple() method.
   *
   * Those value we are getting from testSetMultiple() function.
   *
   * @param \Drupal\Core\State\State $state
   *   The tested state.
   *
   * @legacy-covers ::delete
   * @legacy-covers ::deleteMultiple
   */
  #[Depends('testSetMultiple')]
  public function testDelete(State $state): void {
    $state->delete('key1');

    $this->assertEquals(NULL, $state->get('key1'));
    $this->assertEquals(['key1' => NULL], $state->getMultiple(['key1']));

    $this->assertEquals('value2', $state->get('key2'));
    $this->assertEquals(['key2' => 'value2', 'key3' => 'value3'], $state->getMultiple(['key2', 'key3']));

    $state->deleteMultiple(['key2', 'key3']);
    $this->assertEquals(NULL, $state->get('key2'));
    $this->assertEquals(NULL, $state->get('key3'));
    $this->assertEquals(['key2' => NULL, 'key3' => NULL], $state->getMultiple(['key2', 'key3']));
  }

  /**
   * Tests both get() & delete() method.
   *
   * Here testing the key and value after deleting the key's value.
   *
   * Ensure that deleting clears some static cache.
   *
   * @legacy-covers ::get
   * @legacy-covers ::delete
   */
  public function testDeleteAfterGet(): void {
    $values = ['key' => 'value'];
    $this->keyValueStorage->expects($this->once())
      ->method('setMultiple')
      ->with($values);

    $this->state->setMultiple($values);

    $this->assertEquals('value', $this->state->get('key'));
    $this->state->delete('key');
    $this->assertEquals(NULL, $this->state->get('key'));
  }

  /**
   * Tests both deleteMultiple() method.
   *
   * Here testing the multiple key and value after deleting
   * the key's value in one go.
   */
  public function testDeleteMultiple(): void {
    $values = ['key1' => 'value1', 'key2' => 'value2'];
    $this->keyValueStorage->expects($this->once())
      ->method('setMultiple')
      ->with($values);

    $this->state->setMultiple($values);

    $this->state->deleteMultiple(['key1', 'key2']);

    $this->assertEquals(['key1' => NULL, 'key2' => NULL], $this->state->getMultiple(['key1', 'key2']));
  }

  /**
   * Tests both resetCache(), get() and getMultiple() method.
   *
   * Here testing the get() and getMultiple() functions both before after
   * calling resetCache() function.
   *
   * @param \Drupal\Core\State\State $state
   *   The tested state.
   *
   * @legacy-covers ::resetCache
   * @legacy-covers ::get
   * @legacy-covers ::getMultiple
   */
  #[Depends('testSet')]
  public function testResetCache(State $state): void {
    $this->assertEquals('value', $state->get('key'));
    $this->state->resetCache();
    $this->assertEquals('value', $state->get('key'));

    $this->assertEquals(['key' => 'value'], $state->getMultiple(['key']));
    $this->state->resetCache();
    $this->assertEquals(['key' => 'value'], $state->getMultiple(['key']));
  }

  /**
   * Tests the ::getValuesSetDuringRequest() method.
   */
  public function testGetValuesSetDuringRequest(): void {
    // Confirm getValuesSetDuringRequest() returns the correct values for
    // new keys set in state by setMultiple() during the request.
    $values = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
    $this->state->setMultiple($values);
    $this->assertSame(['value' => 'value1', 'original' => NULL], $this->state->getValuesSetDuringRequest('key1'));
    $this->assertSame(['value' => 'value2', 'original' => NULL], $this->state->getValuesSetDuringRequest('key2'));
    $this->assertSame(['value' => 'value3', 'original' => NULL], $this->state->getValuesSetDuringRequest('key3'));
    // Confirm that getValuesSetDuringRequest() returns NULL for key not set in
    // state during the request.
    $this->assertNull($this->state->getValuesSetDuringRequest('key4'));

    // Confirm that getValuesSetDuringRequest() returns the correct values for
    // new keys set in state by setMultiple() during the request and that
    // values for keys previously set during the request are not overwritten.
    $nonOverwritingValues = ['key4' => 'value4', 'key5' => 'value5', 'key6' => 'value6'];
    $this->state->setMultiple($nonOverwritingValues);
    $this->assertSame(['value' => 'value1', 'original' => NULL], $this->state->getValuesSetDuringRequest('key1'));
    $this->assertSame(['value' => 'value2', 'original' => NULL], $this->state->getValuesSetDuringRequest('key2'));
    $this->assertSame(['value' => 'value3', 'original' => NULL], $this->state->getValuesSetDuringRequest('key3'));
    $this->assertSame(['value' => 'value4', 'original' => NULL], $this->state->getValuesSetDuringRequest('key4'));
    $this->assertSame(['value' => 'value5', 'original' => NULL], $this->state->getValuesSetDuringRequest('key5'));
    $this->assertSame(['value' => 'value6', 'original' => NULL], $this->state->getValuesSetDuringRequest('key6'));

    // Confirm getValuesSetDuringRequest() returns the correct new values for
    // keys set by setMultiple() again in state during the request.
    $overwritingValues = ['key5' => 'new-value-5', 'key6' => 'new-value-6'];
    $this->state->setMultiple($overwritingValues);
    $this->assertSame(['value' => 'new-value-5', 'original' => NULL], $this->state->getValuesSetDuringRequest('key5'));
    $this->assertSame(['value' => 'new-value-6', 'original' => NULL], $this->state->getValuesSetDuringRequest('key6'));

    // Confirm getValuesSetDuringRequest() returns the correct new value for
    // a key set in state by set() during the request.
    $this->state->set('key4', 'new-value-4');
    $this->assertSame(['value' => 'new-value-4', 'original' => NULL], $this->state->getValuesSetDuringRequest('key4'));
  }

  /**
   * Tests getValuesSetDuringRequest() method with an existing value.
   *
   * @legacy-covers ::getValuesSetDuringRequest
   */
  public function testExistingGetValuesSetDuringRequest(): void {
    // Mock the state system in order to set "value" as the value for the key
    // "existing". This simulates this value already being set before the
    // request.
    $keyValueStorage = $this->getMockBuilder(KeyValueStoreInterface::class)->getMock();
    $keyValueStorage->expects($this->once())->method('get')->with('existing')->willReturn('value');
    $factory = $this->getMockBuilder(KeyValueFactoryInterface::class)->getMock();
    $factory->expects($this->once())
      ->method('get')
      ->with('state')
      ->willReturn($keyValueStorage);
    $lock = $this->getMockBuilder(LockBackendInterface::class)->getMock();
    $cache = $this->getMockBuilder(CacheBackendInterface::class)
      ->getMock();
    $state = new State($factory, $cache, $lock);
    // Confirm that the 'original' property returned by
    // getValuesSetDuringRequest() has the value set before the current request,
    // and that the 'value' property has the new value.
    $state->set('existing', 'new-value');
    $this->assertSame(['value' => 'new-value', 'original' => 'value'], $state->getValuesSetDuringRequest('existing'));
    // Confirm that setting the value again in the same request correctly
    // updates the 'value' property returned by getValuesSetDuringRequest(), but
    // the value for the 'original' property remains the same as what it was
    // set to before the request.
    $state->set('existing', 'newer-value');
    $this->assertSame(['value' => 'newer-value', 'original' => 'value'], $state->getValuesSetDuringRequest('existing'));
  }

}

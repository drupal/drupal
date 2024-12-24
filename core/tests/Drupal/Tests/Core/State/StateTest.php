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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\Core\State\State
 * @group State
 */
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
   * @covers ::get
   * @covers ::getMultiple
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
   * @covers ::get
   * @covers ::getMultiple
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
   * @covers ::get
   * @covers ::getMultiple
   *
   * @depends testGet
   */
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
   *
   * @covers ::getMultiple
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
   *
   * @covers ::getMultiple
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
   *
   * @covers ::getMultiple
   *
   * @depends testGetMultiple
   */
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
   *
   * @covers ::getMultiple
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
   *
   * @covers ::set
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
   * @covers ::get
   *
   * @depends testSet
   */
  public function testSetBeforeGet(State $state) {
    $this->assertEquals('value', $state->get('key'));
  }

  /**
   * Tests setMultiple() method.
   *
   * Here we are saving multiple key value pare in one go. Those value will be
   * used in testResetCache() and testSetBeforeGet() functions.
   *
   * @covers ::setMultiple
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
   * @covers ::getMultiple
   *
   * @depends testSetMultiple
   */
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
   * @covers ::delete
   * @covers ::deleteMultiple
   *
   * @depends testSetMultiple
   */
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
   * @covers ::get
   * @covers ::delete
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
   *
   * @covers ::deleteMultiple
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
   * @covers ::resetCache
   * @covers ::get
   * @covers ::getMultiple
   *
   * @depends testSet
   */
  public function testResetCache(State $state): void {
    $this->assertEquals('value', $state->get('key'));
    $this->state->resetCache();
    $this->assertEquals('value', $state->get('key'));

    $this->assertEquals(['key' => 'value'], $state->getMultiple(['key']));
    $this->state->resetCache();
    $this->assertEquals(['key' => 'value'], $state->getMultiple(['key']));
  }

}

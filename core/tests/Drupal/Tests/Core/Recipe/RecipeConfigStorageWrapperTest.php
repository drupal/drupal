<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Recipe;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Recipe\RecipeConfigStorageWrapper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Recipe\RecipeConfigStorageWrapper
 * @group Recipe
 */
class RecipeConfigStorageWrapperTest extends UnitTestCase {

  /**
   * Validate that an empty set of storage backends returns null storage.
   */
  public function testNullStorage(): void {
    $this->assertInstanceOf(
      NullStorage::class,
      RecipeConfigStorageWrapper::createStorageFromArray([])
    );
  }

  /**
   * Validate that a single storage returns exactly the same instance.
   */
  public function testSingleStorage(): void {
    $storages = [new NullStorage()];
    $this->assertSame(
      $storages[0],
      RecipeConfigStorageWrapper::createStorageFromArray($storages)
    );
  }

  /**
   * Validate that multiple storages return underlying values correctly.
   */
  public function testMultipleStorages(): void {
    $a = new MemoryStorage();
    $a->write('a_key', ['a_data_first']);
    $b = new MemoryStorage();

    // Add a conflicting key so that we can test the first value is returned.
    $b->write('a_key', ['a_data_second']);
    $b->write('b_key', ['b_data']);

    // We test with a third storage as well since only two storages can be done
    // via the constructor alone.
    $c = new MemoryStorage();
    $c->write('c_key', ['c_data']);

    $storages = [$a, $b, $c];
    $wrapped = RecipeConfigStorageWrapper::createStorageFromArray($storages);

    $this->assertSame($a->read('a_key'), $wrapped->read('a_key'));
    $this->assertNotEquals($b->read('a_key'), $wrapped->read('a_key'));
    $this->assertSame($b->read('b_key'), $wrapped->read('b_key'));
    $this->assertSame($c->read('c_key'), $wrapped->read('c_key'));
  }

  /**
   * Validate that the first storage checks existence first.
   */
  public function testLeftSideExists(): void {
    $a = $this->createMock(StorageInterface::class);
    $a->expects($this->once())->method('exists')->with('a_key')
      ->willReturn(TRUE);
    $b = $this->createMock(StorageInterface::class);
    $b->expects($this->never())->method('exists');

    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertTrue($storage->exists('a_key'));
  }

  /**
   * Validate that we fall back to the second storage.
   */
  public function testRightSideExists(): void {
    [$a, $b] = $this->generateStorages(TRUE);

    $storage = new RecipeConfigStorageWrapper($a, $b);
    $storage->exists('a_key');
  }

  /**
   * Validate FALSE when neither storage contains a key.
   */
  public function testNotExists(): void {
    [$a, $b] = $this->generateStorages(FALSE);

    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertFalse($storage->exists('a_key'));
  }

  /**
   * Validate that we read from storage A first.
   */
  public function testReadFromA(): void {
    $a = $this->createMock(StorageInterface::class);
    $value = ['a_value'];
    $a->expects($this->once())->method('read')->with('a_key')
      ->willReturn($value);
    $b = $this->createMock(StorageInterface::class);
    $b->expects($this->never())->method('read');

    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertSame($value, $storage->read('a_key'));
  }

  /**
   * Validate that we read from storage B second.
   */
  public function testReadFromB(): void {
    $a = $this->createMock(StorageInterface::class);
    $a->expects($this->once())->method('read')->with('a_key')
      ->willReturn(FALSE);
    $b = $this->createMock(StorageInterface::class);
    $value = ['a_value'];
    $b->expects($this->once())->method('read')->with('a_key')
      ->willReturn($value);

    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertSame($value, $storage->read('a_key'));
  }

  /**
   * Validate when neither storage can read a value.
   */
  public function testReadFails(): void {
    $a = $this->createMock(StorageInterface::class);
    $a->expects($this->once())->method('read')->with('a_key')
      ->willReturn(FALSE);
    $b = $this->createMock(StorageInterface::class);
    $b->expects($this->once())->method('read')->with('a_key')
      ->willReturn(FALSE);

    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertFalse($storage->read('a_key'));
  }

  /**
   * Test reading multiple values.
   */
  public function testReadMultiple(): void {
    $a = $this->createMock(StorageInterface::class);
    $a->expects($this->once())->method('readMultiple')->with(['a_key', 'b_key'])
      ->willReturn(['a_key' => ['a_value']]);
    $b = $this->createMock(StorageInterface::class);
    $b->expects($this->once())->method('readMultiple')->with(['a_key', 'b_key'])
      ->willReturn(['b_key' => ['b_value']]);

    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertEquals([
      'a_key' => ['a_value'],
      'b_key' => ['b_value'],
    ], $storage->readMultiple(['a_key', 'b_key']));
  }

  /**
   * Test that storage A has precedence over storage B.
   */
  public function testReadMultipleStorageA(): void {
    $a = $this->createMock(StorageInterface::class);
    $a->expects($this->once())->method('readMultiple')->with(['a_key', 'b_key'])
      ->willReturn(['a_key' => ['a_value']]);
    $b = $this->createMock(StorageInterface::class);
    $b->expects($this->once())->method('readMultiple')->with(['a_key', 'b_key'])
      ->willReturn(['a_key' => ['a_conflicting_value'], 'b_key' => ['b_value']]);

    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertEquals([
      'a_key' => ['a_value'],
      'b_key' => ['b_value'],
    ], $storage->readMultiple(['a_key', 'b_key']));
  }

  /**
   * Test methods that are unsupported.
   *
   * @param string $method
   *   The method to call.
   * @param array $args
   *   The arguments to pass to the method.
   *
   * @testWith ["write", "name", []]
   *           ["delete", "name"]
   *           ["rename", "old_name", "new_name"]
   *           ["deleteAll"]
   */
  public function testUnsupportedMethods(string $method, ...$args): void {
    $this->expectException(\BadMethodCallException::class);
    $storage = new RecipeConfigStorageWrapper(new NullStorage(), new NullStorage());
    $storage->{$method}(...$args);
  }

  /**
   * Test that we only use storage A's encode method.
   */
  public function testEncode(): void {
    $a = $this->createMock(StorageInterface::class);
    $b = $this->createMock(StorageInterface::class);
    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->expectException(\BadMethodCallException::class);
    $storage->encode(['value']);
  }

  /**
   * Test that we only use storage A's decode method.
   */
  public function testDecode(): void {
    $a = $this->createMock(StorageInterface::class);
    $b = $this->createMock(StorageInterface::class);
    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->expectException(\BadMethodCallException::class);
    $storage->decode('value');
  }

  /**
   * Test that list all merges values and makes them unique.
   */
  public function testListAll(): void {
    $a = $this->createMock(StorageInterface::class);
    $a->method('listAll')->with('node.')
      ->willReturn(['node.type']);
    $b = $this->createMock(StorageInterface::class);
    $b->method('listAll')->with('node.')
      ->willReturn(['node.type', 'node.id']);
    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertEquals([
      0 => 'node.type',
      2 => 'node.id',
    ], $storage->listAll('node.'));
  }

  /**
   * Test creating a collection passes the name through to the child storages.
   */
  public function testCreateCollection(): void {
    $collection_name = 'collection';
    $a = $this->createMock(StorageInterface::class);
    $b = $this->createMock(StorageInterface::class);
    /** @var \PHPUnit\Framework\MockObject\MockObject $mock */
    foreach ([$a, $b] as $mock) {
      $mock->expects($this->once())->method('createCollection')
        ->with($collection_name)->willReturn(new NullStorage($collection_name));
    }
    $storage = new RecipeConfigStorageWrapper($a, $b);
    $new = $storage->createCollection($collection_name);
    $this->assertInstanceOf(RecipeConfigStorageWrapper::class, $new);
    $this->assertEquals($collection_name, $new->getCollectionName());
    $this->assertNotEquals($storage, $new);
  }

  /**
   * Test that we merge and return only unique collection names.
   */
  public function testGetAllCollectionNames(): void {
    $a = $this->createMock(StorageInterface::class);
    $a->expects($this->once())->method('getAllCollectionNames')
      ->willReturn(['collection_1', 'collection_2']);
    $b = $this->createMock(StorageInterface::class);
    $b->expects($this->once())->method('getAllCollectionNames')
      ->willReturn(['collection_3', 'collection_1', 'collection_2']);
    $storage = new RecipeConfigStorageWrapper($a, $b);
    $this->assertEquals([
      'collection_1',
      'collection_2',
      'collection_3',
    ], $storage->getAllCollectionNames());
  }

  /**
   * Test the collection name is stored properly.
   */
  public function testGetCollection(): void {
    $a = $this->createMock(StorageInterface::class);
    $b = $this->createMock(StorageInterface::class);
    $storage = new RecipeConfigStorageWrapper($a, $b, 'collection');
    $this->assertEquals('collection', $storage->getCollectionName());
  }

  /**
   * Generate two storages where the second storage should return a value.
   *
   * @param bool $b_return
   *   The return value for storage $b's exist method.
   *
   * @return \Drupal\Core\Config\StorageInterface[]
   *   An array of two mocked storages.
   */
  private function generateStorages(bool $b_return): array {
    $a = $this->createMock(StorageInterface::class);
    $a->expects($this->once())->method('exists')->with('a_key')
      ->willReturn(FALSE);
    $b = $this->createMock(StorageInterface::class);
    $b->expects($this->once())->method('exists')->with('a_key')
      ->willReturn($b_return);
    return [$a, $b];
  }

}

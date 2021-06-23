<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\ChunkedIterator;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @group Entity
 * @coversDefaultClass \Drupal\Core\Entity\ChunkedIterator
 */
class ChunkedIteratorTest extends UnitTestCase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entity;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityType;

  /**
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $memoryCache;

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->memoryCache = $this->prophesize(MemoryCacheInterface::class);
    $this->entity = $this->prophesize(EntityInterface::class);
    $this->entityType = $this->prophesize(EntityTypeInterface::class);
  }

  /**
   * @covers ::count
   */
  public function testCountWithNoItems() {
    $this->entityStorage->loadMultiple()->shouldNotBeCalled();
    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), []);
    $this->assertSame(0, $iterator->count());
  }

  /**
   * @covers ::getIterator
   */
  public function testIterationWithNoItems() {
    $this->entityStorage->loadMultiple()->shouldNotBeCalled();

    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), []);
    iterator_to_array($iterator);
  }

  /**
   * @covers ::getIterator
   */
  public function testIteratorWithNoValidItems() {
    $this->memoryCache->deleteAll()
      ->shouldBeCalledTimes(1);
    $this->entityStorage->loadMultiple([1, 2, 3])
      ->willReturn([])
      ->shouldBeCalled();

    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), [1, 2, 3]);
    iterator_to_array($iterator);
  }

  /**
   * @covers ::getIterator
   */
  public function testIteratorWithOneChunkValidItems() {
    $return = [
      1 => $this->entity->reveal(),
      2 => $this->entity->reveal(),
      3 => $this->entity->reveal(),
    ];

    $this->memoryCache->deleteAll()
      ->shouldBeCalledTimes(1);
    $this->entityStorage->loadMultiple([1, 2, 3])
      ->willReturn($return)
      ->shouldBeCalled();

    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), [1, 2, 3]);

    $this->assertSame($return, iterator_to_array($iterator));
  }

  /**
   * @covers ::getIterator
   */
  public function testIteratorWithOneChunkInvalidItems() {
    $return = [
      2 => $this->entity->reveal(),
      3 => $this->entity->reveal(),
    ];

    $this->memoryCache->deleteAll()
      ->shouldBeCalledTimes(1);
    $this->entityStorage->loadMultiple([1, 2, 3])
      ->willReturn($return)
      ->shouldBeCalled();

    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), [1, 2, 3]);

    $this->assertSame($return, iterator_to_array($iterator));
  }

  /**
   * @covers ::getIterator
   */
  public function testIteratorWithMultipleChunkValidItems() {
    $return_1 = [
      1 => $this->entity->reveal(),
      2 => $this->entity->reveal(),
      3 => $this->entity->reveal(),
    ];

    $return_2 = [
      4 => $this->entity->reveal(),
      5 => $this->entity->reveal(),
      6 => $this->entity->reveal(),
    ];

    $this->memoryCache->deleteAll()
      ->shouldBeCalledTimes(2);
    $this->entityStorage->loadMultiple([1, 2, 3])
      ->willReturn($return_1)
      ->shouldBeCalled();
    $this->entityStorage->loadMultiple([4, 5, 6])
      ->willReturn($return_2)
      ->shouldBeCalled();

    // Create a new iterator but set the cache limit to 3. Two chunks should be
    // loaded.
    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), [1, 2, 3, 4, 5, 6], 3);

    $this->assertSame($return_1 + $return_2, iterator_to_array($iterator));
  }

  /**
   * @covers ::getIterator
   */
  public function testIteratorWithGenerator() {
    $return_1 = [
      1 => $this->entity->reveal(),
      2 => $this->entity->reveal(),
      3 => $this->entity->reveal(),
    ];

    $return_2 = [
      4 => $this->entity->reveal(),
      5 => $this->entity->reveal(),
      6 => $this->entity->reveal(),
    ];

    $this->memoryCache->deleteAll()
      ->shouldBeCalledTimes(2);
    $this->entityStorage->loadMultiple([1, 2, 3])
      ->willReturn($return_1)
      ->shouldBeCalled();
    $this->entityStorage->loadMultiple([4, 5, 6])
      ->willReturn($return_2)
      ->shouldBeCalled();

    $ids = function () {
      for ($i = 1; $i <= 6; $i++) {
        yield $i;
      }
    };

    // Create a new iterator but set the cache limit to 3. Two chunks should be
    // loaded.
    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), $ids(), 3);

    $this->assertSame($return_1 + $return_2, iterator_to_array($iterator));
  }

  /**
   * @covers ::getIterator
   */
  public function testIteratorWithArrayIterator() {
    $return_1 = [
      1 => $this->entity->reveal(),
      2 => $this->entity->reveal(),
      3 => $this->entity->reveal(),
    ];

    $return_2 = [
      4 => $this->entity->reveal(),
      5 => $this->entity->reveal(),
      6 => $this->entity->reveal(),
    ];

    $this->memoryCache->deleteAll()
      ->shouldBeCalledTimes(2);
    $this->entityStorage->loadMultiple([1, 2, 3])
      ->willReturn($return_1)
      ->shouldBeCalled();
    $this->entityStorage->loadMultiple([4, 5, 6])
      ->willReturn($return_2)
      ->shouldBeCalled();

    $ids = new \ArrayIterator([1, 2, 3, 4, 5, 6]);

    // Create a new iterator but set the cache limit to 3. Two chunks should be
    // loaded.
    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), $ids, 3);

    $this->assertSame($return_1 + $return_2, iterator_to_array($iterator));
  }

  /**
   * @covers ::getIterator
   */
  public function testIteratorWithMultipleChunkInvalidItems() {
    $return_1 = [
      2 => $this->entity->reveal(),
      3 => $this->entity->reveal(),
    ];

    $return_2 = [
      5 => $this->entity->reveal(),
      6 => $this->entity->reveal(),
    ];

    $this->memoryCache->deleteAll()
      ->shouldBeCalledTimes(2);
    $this->entityStorage->loadMultiple([1, 2, 3])
      ->willReturn($return_1)
      ->shouldBeCalled();
    $this->entityStorage->loadMultiple([4, 5, 6])
      ->willReturn($return_2)
      ->shouldBeCalled();

    // Create a new iterator but set the cache limit to 3. Two chunks should be
    // loaded.
    $iterator = new ChunkedIterator($this->entityStorage->reveal(), $this->memoryCache->reveal(), [1, 2, 3, 4, 5, 6], 3);

    $this->assertSame($return_1 + $return_2, iterator_to_array($iterator));
  }

}

<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;

/**
 * Provides an Iterator class for dealing with large amounts of entities.
 *
 * Common usecases for this iterator is in a hook_post_update() hook if you need
 * to load all entities of a type, or in some command line utility.
 *
 * Example:
 * @code
 * $iterator = new ChunkedIterator($entity_storage, \Drupal::service('entity.memory_cache'), $all_ids);
 * foreach ($iterator as $entity) {
 *   // Process the entity
 * }
 * @endcode
 */
class ChunkedIterator implements \IteratorAggregate, \Countable {

  /**
   * The entity storage controller to load entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * An iterable of entity IDs to iterate over.
   *
   * @var iterable
   */
  protected $entityIds;

  /**
   * The size of each chunk of loaded entities.
   *
   * @var int
   */
  protected $chunkSize;

  /**
   * The memory cache to store but also reset loaded entities.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected $memoryCache;

  /**
   * Constructs an entity iterator object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache service.
   * @param iterable $ids
   *   An iterable of entity IDs. Common examples would be an array or a
   *   generator.
   * @param int $chunk_size
   *   The size per chunk we want to load in parallel.
   */
  public function __construct(EntityStorageInterface $entity_storage, MemoryCacheInterface $memory_cache, iterable $ids, $chunk_size = 50) {
    $this->entityStorage = $entity_storage;
    $this->memoryCache = $memory_cache;
    // Make sure we don't use a keyed array.
    $this->entityIds = $ids;
    $this->chunkSize = (int) $chunk_size;
  }

  /**
   * @inheritdoc
   */
  public function count() {
    return count($this->entityIds);
  }

  /**
   * @inheritdoc
   */
  public function getIterator() {
    foreach ($this->chunkIds() as $ids_chunk) {
      yield from $this->entityStorage->loadMultiple($ids_chunk);
      // We clear all memory cache as we want to remove all referenced entities
      // as well, like for example the owner of an entity.
      $this->memoryCache->deleteAll();
    }
  }

  /**
   * Chunks the entity IDs, whether its an array of an iterable.
   *
   * @return iterable
   */
  protected function chunkIds() : iterable {
    // Optimize for the entity IDs be in the form of an array already.
    if (is_array($this->entityIds)) {
      foreach (array_chunk(array_values($this->entityIds), $this->chunkSize) as $chunk) {
        yield $chunk;
      }
    }
    else {
      $chunk = [];
      foreach ($this->entityIds as $id) {
        $chunk[] = $id;
        if (count($chunk) == $this->chunkSize) {
          yield $chunk;
          $chunk = [];
        }
      }

      if (count($chunk)) {
        yield $chunk;
      }
    }

  }

}

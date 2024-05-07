<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Merges two storages together.
 *
 * @internal
 *   This API is experimental.
 */
final class RecipeConfigStorageWrapper implements StorageInterface {

  /**
   * @param \Drupal\Core\Config\StorageInterface $storageA
   *   First config storage to wrap.
   * @param \Drupal\Core\Config\StorageInterface $storageB
   *   Second config storage to wrap.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   */
  public function __construct(
    protected readonly StorageInterface $storageA,
    protected readonly StorageInterface $storageB,
    protected readonly string $collection = StorageInterface::DEFAULT_COLLECTION,
  ) {
  }

  /**
   * Creates a single config storage for an array of storages.
   *
   * If the same configuration is contained in multiple storages then the
   * version returned is from the first storage supplied in the $storages array.
   *
   * @param \Drupal\Core\Config\StorageInterface[] $storages
   *   An array of storages to merge into a single storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   A config storage that represents a merge of all the provided storages.
   */
  public static function createStorageFromArray(array $storages): StorageInterface {
    // If storages is empty use the NullStorage to represent an empty storage.
    if (empty($storages)) {
      return new NullStorage();
    }

    // When there is only one storage there is no point wrapping it.
    if (count($storages) === 1) {
      return reset($storages);
    }

    // Reduce all the storages to a single RecipeConfigStorageWrapper object.
    // The storages are prioritized in the order they are added to $storages.
    return array_reduce($storages, fn(StorageInterface $carry, StorageInterface $storage) => new static($carry, $storage), new static(
      array_shift($storages),
      array_shift($storages)
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    return $this->storageA->exists($name) || $this->storageB->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name): array|bool {
    return $this->storageA->read($name) ?: $this->storageB->read($name);
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names): array {
    // If both storageA and storageB contain the same configuration, the value
    // for storageA takes precedence.
    return array_merge($this->storageB->readMultiple($names), $this->storageA->readMultiple($names));
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data): bool {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name): bool {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name): bool {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data): string {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw): array {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = ''): array {
    return array_unique(array_merge($this->storageA->listAll($prefix), $this->storageB->listAll($prefix)));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = ''): bool {
    throw new \BadMethodCallException();
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection): static {
    return new static(
      $this->storageA->createCollection($collection),
      $this->storageB->createCollection($collection),
      $collection
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames(): array {
    return array_unique(array_merge($this->storageA->getAllCollectionNames(), $this->storageB->getAllCollectionNames()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName(): string {
    return $this->collection;
  }

}

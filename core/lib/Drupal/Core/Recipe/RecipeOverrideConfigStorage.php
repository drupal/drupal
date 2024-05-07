<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Config\StorageInterface;

/**
 * Wraps a config storage to allow recipe provided configuration to override it.
 *
 * @internal
 *   This API is experimental.
 */
final class RecipeOverrideConfigStorage implements StorageInterface {

  /**
   * @param \Drupal\Core\Config\StorageInterface $recipeStorage
   *   The recipe's configuration storage.
   * @param \Drupal\Core\Config\StorageInterface $wrappedStorage
   *   The storage to override.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   */
  public function __construct(
    protected readonly StorageInterface $recipeStorage,
    protected readonly StorageInterface $wrappedStorage,
    protected readonly string $collection = StorageInterface::DEFAULT_COLLECTION,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    return $this->wrappedStorage->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name): array|bool {
    if ($this->wrappedStorage->exists($name) && $this->recipeStorage->exists($name)) {
      return $this->recipeStorage->read($name);
    }
    return $this->wrappedStorage->read($name);
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names): array {
    $data = $this->wrappedStorage->readMultiple($names);
    foreach ($data as $name => $value) {
      if ($this->recipeStorage->exists($name)) {
        $data[$name] = $this->recipeStorage->read($name);
      }
    }
    return $data;
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
    return $this->wrappedStorage->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw): array {
    return $this->wrappedStorage->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = ''): array {
    return $this->wrappedStorage->listAll($prefix);
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
      $this->recipeStorage->createCollection($collection),
      $this->wrappedStorage->createCollection($collection),
      $collection
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames(): array {
    return $this->wrappedStorage->getAllCollectionNames();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName(): string {
    return $this->collection;
  }

}

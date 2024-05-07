<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Allows the recipe to select configuration from the module.
 *
 * @internal
 *   This API is experimental.
 */
final class RecipeExtensionConfigStorage implements StorageInterface {

  protected readonly StorageInterface $storage;

  /**
   * @param string $extensionPath
   *   The path extension to read configuration from
   * @param array $configNames
   *   The list of config to read from the extension. An empty array means all
   *   configuration.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   */
  public function __construct(protected readonly string $extensionPath, protected readonly array $configNames, protected readonly string $collection = StorageInterface::DEFAULT_COLLECTION) {
    $this->storage = new RecipeConfigStorageWrapper(
      new FileStorage($this->extensionPath . '/config/install', $this->collection),
      new FileStorage($this->extensionPath . '/config/optional', $this->collection),
      $collection
    );
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    if (!empty($this->configNames) && !in_array($name, $this->configNames, TRUE)) {
      return FALSE;
    }
    return $this->storage->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name): array|bool {
    if (!empty($this->configNames) && !in_array($name, $this->configNames, TRUE)) {
      return FALSE;
    }
    return $this->storage->read($name);
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names): array {
    if (!empty($this->configNames)) {
      $names = array_intersect($this->configNames, $names);
    }
    return $this->storage->readMultiple($names);
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
    $names = $this->storage->listAll($prefix);
    if (!empty($this->configNames)) {
      $names = array_intersect($this->configNames, $names);
    }
    return $names;
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
      $this->extensionPath,
      $this->configNames,
      $collection
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames(): array {
    return $this->storage->getAllCollectionNames();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName(): string {
    return $this->collection;
  }

}

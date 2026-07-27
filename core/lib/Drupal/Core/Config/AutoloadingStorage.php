<?php

declare(strict_types=1);

namespace Drupal\Core\Config;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Defines the autoloading storage.
 *
 * Allows configuration to be read that contains constants and enum from
 * extensions that are not yet installed. To do so, a class loader is
 * temporarily registered while reading from configuration.
 */
class AutoloadingStorage implements StorageInterface, StorageCacheInterface {

  use DependencySerializationTrait;

  /**
   * Class loader that loads code from extensions provided to the constructor.
   */
  protected ?ClassLoader $classloader = NULL;

  /**
   * Constructs a new AutoloadingStorage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A configuration storage to be wrapped.
   * @param string[] $newExtensions
   *   A list of new extensions in the source storage that we need to allow to
   *   autoload while reading. The values should be absolute paths to the
   *   extension directory.
   */
  public function __construct(protected StorageInterface $storage, array $newExtensions = []) {
    if (!empty($newExtensions)) {
      $this->classloader = new ClassLoader();
      foreach ($newExtensions as $extension => $dir) {
        $this->classloader->addPsr4("Drupal\\$extension\\", $dir . '/src');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    return $this->storage->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name): array|false {
    $this->classloader?->register(TRUE);
    try {
      $data = $this->storage->read($name);
    }
    finally {
      $this->classloader?->unregister();
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names): array {
    $this->classloader?->register(TRUE);
    try {
      $data = $this->storage->readMultiple($names);
    }
    finally {
      $this->classloader?->unregister();
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data): bool {
    return $this->storage->write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name): bool {
    return $this->storage->delete($name);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name): bool {
    return $this->storage->rename($name, $new_name);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data): string {
    return $this->storage->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw): array {
    return $this->storage->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = ''): array {
    return $this->storage->listAll($prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = ''): bool {
    return $this->storage->deleteAll($prefix);
  }

  /**
   * Clears the static list cache.
   */
  public function resetListCache(): void {
    if ($this->storage instanceof StorageCacheInterface) {
      $this->storage->resetListCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection): static {
    $storage = new static(
      $this->storage->createCollection($collection)
    );
    $storage->classloader = $this->classloader;
    return $storage;
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
    return $this->storage->getCollectionName();
  }

}

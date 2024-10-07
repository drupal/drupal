<?php

declare(strict_types=1);

namespace Drupal\Core\Recipe;

use Drupal\Core\Config\StorageInterface;

/**
 * A read-only storage wrapper that only allows access to certain config names.
 *
 * @internal
 *   This API is experimental.
 */
final class AllowListConfigStorage implements StorageInterface {

  /**
   * @param \Drupal\Core\Config\StorageInterface $decorated
   *   A config storage backend to wrap around.
   * @param string[] $allowList
   *   A list of config names. Only these names will be visible, or readable,
   *   by this storage. Cannot be empty.
   */
  public function __construct(
    private readonly StorageInterface $decorated,
    private readonly array $allowList,
  ) {
    if (empty($allowList)) {
      throw new \LogicException('AllowListConfigStorage cannot be constructed with an empty allow list.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name): bool {
    if (in_array($name, $this->allowList, TRUE)) {
      return $this->decorated->exists($name);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name): array|false {
    return $this->exists($name) ? $this->decorated->read($name) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names): array {
    $names = array_intersect($names, $this->allowList);
    return $this->decorated->readMultiple($names);
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data): never {
    throw new \BadMethodCallException('This storage is read-only.');
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name): never {
    throw new \BadMethodCallException('This storage is read-only.');
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name): never {
    throw new \BadMethodCallException('This storage is read-only.');
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data): string {
    return $this->decorated->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw): array {
    return $this->decorated->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = ''): array {
    return array_intersect($this->decorated->listAll($prefix), $this->allowList);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = ''): never {
    throw new \BadMethodCallException('This storage is read-only.');
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection): static {
    return new static(
      $this->decorated->createCollection($collection),
      $this->allowList,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames(): array {
    return $this->decorated->getAllCollectionNames();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName(): string {
    return $this->decorated->getCollectionName();
  }

}

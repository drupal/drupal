<?php

namespace Drupal\history;

use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;

/**
 * Provides history repository service.
 */
class HistoryRepository implements HistoryRepositoryInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The memory cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $memoryCache;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs the history repository service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(Connection $connection, TimeInterface $time, MemoryCacheInterface $memory_cache, AccountInterface $current_user) {
    $this->connection = $connection;
    $this->time = $time;
    $this->memoryCache = $memory_cache;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastViewed(string $entity_type, array $entity_ids): array {
    $entities = [];
    $entities_to_read = [];
    $user_id = $this->currentUser->id();
    foreach ($entity_ids as $entity_id) {
      // Load from the cache.
      $cached = $this->memoryCache->get(
        $this->buildCacheId($user_id, $entity_type, $entity_id
      ));
      if ($cached) {
        $entities[$entity_id] = $cached->data;
      }
      else {
        $entities_to_read[$entity_id] = 0;
      }
    }

    if (empty($entities_to_read)) {
      return $entities;
    }

    $result = $this->connection->select('history', 'h')
      ->fields('h', ['entity_id', 'timestamp'])
      ->condition('uid', $user_id)
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', array_keys($entities_to_read), 'IN')
      ->execute();

    foreach ($result as $row) {
      $timestamp = (int) $row->timestamp;
      $this->memoryCache->set(
        $this->buildCacheId($user_id, $entity_type, $row->entity_id),
        $timestamp,
        Cache::PERMANENT,
        $this->getCacheTags($user_id, $row->entity_id)
      );
      $entities_to_read[$row->entity_id] = $timestamp;
    }

    return $entities + $entities_to_read;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLastViewed(EntityInterface $entity): HistoryRepositoryInterface {
    if ($this->currentUser->isAuthenticated()) {
      $user_id = $this->currentUser->id();
      $time = $this->time->getRequestTime();
      $entity_id = $entity->id();
      $entity_type_id = $entity->getEntityTypeId();
      $this->connection->merge('history')
        ->keys([
          'uid' => $user_id,
          'entity_id' => $entity_id,
          'entity_type' => $entity_type_id,
        ])
        ->fields(['timestamp' => $time])
        ->execute();
      // Update cached value.
      $this->memoryCache->set(
        $this->buildCacheId($user_id, $entity_type_id, $entity_id),
        $time, Cache::PERMANENT,
        $this->getCacheTags($user_id, $entity_id)
      );
    }
    return $this;
  }

  /**
   * Builds the cache ID for the history timestamp.
   *
   * @param int $uid
   *   The User ID.
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return string
   *   Cache ID that can be passed to the cache backend.
   */
  protected function buildCacheId($uid, $entity_type, $entity_id): string {
    return implode(':', ['history', $uid, $entity_type, $entity_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags($user_id, $entity_id): array {
    return [
      'history',
      "history:user:{$user_id}",
      "history:entity:{$entity_id}",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function purge(): void {
    $this->connection->delete('history')
      ->condition('timestamp', HISTORY_READ_LIMIT, '<')
      ->execute();
    // Clean static cache.
    Cache::invalidateTags(['history']);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByUser(AccountInterface $account): void {
    $this->connection->delete('history')
      ->condition('uid', $account->id())
      ->execute();
    // Clean static cache.
    Cache::invalidateTags(["history:user:{$account->id()}"]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByEntity(EntityInterface $entity): void {
    $this->connection->delete('history')
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->execute();
    // Clean static cache.
    Cache::invalidateTags(["history:entity:{$entity->id()}"]);
  }

}

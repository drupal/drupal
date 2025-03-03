<?php

declare(strict_types=1);

namespace Drupal\performance_test\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\performance_test\PerformanceDataCollector;

/**
 * Wraps an existing cache backend to track calls to the cache backend.
 */
class CacheBackendDecorator implements CacheBackendInterface, CacheTagsInvalidatorInterface {

  public function __construct(protected readonly PerformanceDataCollector $performanceDataCollector, protected readonly CacheBackendInterface $cacheBackend, protected readonly string $bin) {}

  /**
   * Logs a cache operation.
   *
   * @param string|array $cids
   *   The cache IDs.
   * @param float $start
   *   The start microtime.
   * @param float $stop
   *   The stop microtime.
   * @param string $operation
   *   The type of operation being logged.
   */
  protected function logCacheOperation(string|array $cids, float $start, float $stop, string $operation): void {
    $this->performanceDataCollector->addCacheOperation([
      'operation' => $operation,
      'cids' => implode(', ', (array) $cids),
      'bin' => $this->bin,
      'start' => $start,
      'stop' => $stop,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE): object|bool {
    $start = microtime(TRUE);
    $cache = $this->cacheBackend->get($cid, $allow_invalid);
    $stop = microtime(TRUE);
    $this->logCacheOperation($cid, $start, $stop, 'get');
    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE): array {
    $cids_copy = $cids;
    $start = microtime(TRUE);
    $cache = $this->cacheBackend->getMultiple($cids, $allow_invalid);
    $stop = microtime(TRUE);
    $this->logCacheOperation($cids_copy, $start, $stop, 'getMultiple');

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    $start = microtime(TRUE);
    $this->cacheBackend->set($cid, $data, $expire, $tags);
    $stop = microtime(TRUE);
    $this->logCacheOperation($cid, $start, $stop, 'set');
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    $cids = array_keys($items);
    $start = microtime(TRUE);
    $this->cacheBackend->setMultiple($items);
    $stop = microtime(TRUE);
    $this->logCacheOperation($cids, $start, $stop, 'setMultiple');
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $start = microtime(TRUE);
    $this->cacheBackend->delete($cid);
    $stop = microtime(TRUE);
    $this->logCacheOperation($cid, $start, $stop, 'delete');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $start = microtime(TRUE);
    $this->cacheBackend->deleteMultiple($cids);
    $stop = microtime(TRUE);
    $this->logCacheOperation($cids, $start, $stop, 'deleteMultiple');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $start = microtime(TRUE);
    $this->cacheBackend->deleteAll();
    $stop = microtime(TRUE);
    $this->logCacheOperation([], $start, $stop, 'deleteAll');
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $start = microtime(TRUE);
    $this->cacheBackend->invalidate($cid);
    $stop = microtime(TRUE);
    $this->logCacheOperation($cid, $start, $stop, 'invalidate');
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    $start = microtime(TRUE);
    $this->cacheBackend->invalidateMultiple($cids);
    $stop = microtime(TRUE);
    $this->logCacheOperation($cids, $start, $stop, 'invalidateMultiple');
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    if ($this->cacheBackend instanceof CacheTagsInvalidatorInterface) {
      $this->cacheBackend->invalidateTags($tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    $start = microtime(TRUE);
    $this->cacheBackend->invalidateAll();
    $stop = microtime(TRUE);
    $this->logCacheOperation([], $start, $stop, 'invalidateAll');
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $this->cacheBackend->garbageCollection();
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->cacheBackend->removeBin();
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Core\Utility;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Caches file parsing in a cache collector.
 */
abstract class FileParsingCacheCollectorBase extends CacheCollector {

  public function __construct(
    $cid,
    CacheBackendInterface $cache,
    LockBackendInterface $lock,
    protected TimeInterface $time,
    array $tags = [],
  ) {
    parent::__construct($cid, $cache, $lock, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key): array {
    $this->lazyLoadCache();
    if (isset($this->storage[$key]) && file_exists($key)) {
      if ($this->storage[$key]['mtime'] === filemtime($key)) {
        return $this->storage[$key]['parsed'];
      }
    }
    return $this->resolveCacheMiss($key);
  }

  /**
   * {@inheritdoc}
   */
  public function resolveCacheMiss($key): array {
    if (file_exists($key)) {
      $mtime = filemtime($key);
      $this->storage[$key] = [
        'mtime' => $mtime,
        'parsed' => $this->parseFile(file_get_contents($key)) ?? [],
      ];
      $this->persist($key);
      return $this->storage[$key]['parsed'];
    }
    if (isset($this->storage[$key])) {
      $this->delete($key);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function updateCache($lock = TRUE): void {
    // Look for files cached longer than three months ago and remove them from
    // the cache item if they no longer exist.
    $three_months_ago = $this->time->getCurrentTime() - (90 * 86400);
    foreach ($this->storage as $key => $item) {
      if (($item['mtime'] <= $three_months_ago) && !file_exists($key)) {
        $this->delete($key);
      }
    }
    parent::updateCache($lock);
  }

  /**
   * Parses a file given a filename and returns the result.
   *
   * @param string $file
   *   The file path.
   *
   * @return mixed
   *   The result of the file parsing.
   */
  abstract protected function parseFile(string $file): mixed;

}

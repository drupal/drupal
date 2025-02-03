<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Assertion\Inspector;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\PhpStorage\PhpStorageInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\PhpStorage\PhpStorageFactory;

/**
 * Defines a PHP cache implementation.
 *
 * Stores cache items in a PHP file using a storage that implements
 * Drupal\Component\PhpStorage\PhpStorageInterface.
 *
 * This is fast because of PHP's opcode caching mechanism. Once a file's
 * content is stored in PHP's opcode cache, including it doesn't require
 * reading the contents from a filesystem. Instead, PHP will use the already
 * compiled opcodes stored in memory.
 *
 * @ingroup cache
 */
class PhpBackend implements CacheBackendInterface {

  /**
   * @var string
   */
  protected $bin;

  /**
   * The PHP storage.
   */
  protected PhpStorageInterface $storage;

  /**
   * Array to store cache objects.
   *
   * @var object[]
   */
  protected $cache = [];

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs a PhpBackend object.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct($bin, CacheTagsChecksumInterface $checksum_provider, protected TimeInterface $time) {
    $this->bin = 'cache_' . $bin;
    $this->checksumProvider = $checksum_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    return $this->getByHash($this->normalizeCid($cid), $allow_invalid);
  }

  /**
   * Fetch a cache item using a hashed cache ID.
   *
   * @param string $cidhash
   *   The hashed version of the original cache ID after being normalized.
   * @param bool $allow_invalid
   *   (optional) If TRUE, a cache item may be returned even if it is expired or
   *   has been invalidated.
   *
   * @return bool|mixed
   *   The requested cached item. Defaults to FALSE when the cache is not set.
   */
  protected function getByHash($cidhash, $allow_invalid = FALSE) {
    if ($file = $this->storage()->getFullPath($cidhash)) {
      $cache = @include $file;
    }
    if (isset($cache)) {
      return $this->prepareItem($cache, $allow_invalid);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], $item['expire'] ?? CacheBackendInterface::CACHE_PERMANENT, $item['tags'] ?? []);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $ret = [];

    foreach ($cids as $cid) {
      if ($item = $this->get($cid, $allow_invalid)) {
        $ret[$item->cid] = $item;
      }
    }

    $cids = array_diff($cids, array_keys($ret));

    return $ret;
  }

  /**
   * Prepares a cached item.
   *
   * Checks that items are either permanent or did not expire, and returns data
   * as appropriate.
   *
   * @param object $cache
   *   An item loaded from self::get() or self::getMultiple().
   * @param bool $allow_invalid
   *   If FALSE, the method returns FALSE if the cache item is not valid.
   *
   * @return mixed
   *   The item with data as appropriate or FALSE if there is no
   *   valid item to load.
   */
  protected function prepareItem($cache, $allow_invalid) {
    if (!isset($cache->data)) {
      return FALSE;
    }

    // Check expire time.
    $cache->valid = $cache->expire == Cache::PERMANENT || $cache->expire >= $this->time->getRequestTime();

    // Check if invalidateTags() has been called with any of the item's tags.
    if (!$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
      $cache->valid = FALSE;
    }

    if (!$allow_invalid && !$cache->valid) {
      return FALSE;
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    assert(Inspector::assertAllStrings($tags), 'Cache Tags must be strings.');

    $item = (object) [
      'cid' => $cid,
      'data' => $data,
      'created' => round(microtime(TRUE), 3),
      'expire' => $expire,
      'tags' => array_unique($tags),
      'checksum' => $this->checksumProvider->getCurrentChecksum($tags),
    ];
    $this->writeItem($this->normalizeCid($cid), $item);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->storage()->delete($this->normalizeCid($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    foreach ($cids as $cid) {
      $this->delete($cid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->storage()->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateByHash($this->normalizeCid($cid));
  }

  /**
   * Invalidate one cache item.
   *
   * @param string $cidhash
   *   The hashed version of the original cache ID after being normalized.
   */
  protected function invalidateByHash($cidhash) {
    if ($item = $this->getByHash($cidhash)) {
      $item->expire = $this->time->getRequestTime() - 1;
      $this->writeItem($cidhash, $item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    foreach ($cids as $cid) {
      $this->invalidate($cid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    @trigger_error("CacheBackendInterface::invalidateAll() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use CacheBackendInterface::deleteAll() or cache tag invalidation instead. See https://www.drupal.org/node/3500622", E_USER_DEPRECATED);
    foreach ($this->storage()->listAll() as $cidhash) {
      $this->invalidateByHash($cidhash);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $this->cache = [];
    $this->storage()->deleteAll();
  }

  /**
   * Writes a cache item to PhpStorage.
   *
   * @param string $cidhash
   *   The hashed version of the original cache ID after being normalized.
   * @param object $item
   *   The cache item to store.
   */
  protected function writeItem($cidhash, \stdClass $item) {
    $content = '<?php return unserialize(' . var_export(serialize($item), TRUE) . ');';
    $this->storage()->save($cidhash, $content);
  }

  /**
   * Gets the PHP code storage object to use.
   *
   * @return \Drupal\Component\PhpStorage\PhpStorageInterface
   *   The PHP storage.
   */
  protected function storage() {
    if (!isset($this->storage)) {
      $this->storage = PhpStorageFactory::get($this->bin);
    }
    return $this->storage;
  }

  /**
   * Ensures a normalized cache ID.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   A normalized cache ID.
   */
  protected function normalizeCid($cid) {
    return Crypt::hashBase64($cid);
  }

}

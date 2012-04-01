<?php

/**
 * @file
 * Definition of CacheArray
 */

namespace Drupal\Core\Utility;

use ArrayAccess;

/**
 * Provides a caching wrapper to be used in place of large array structures.
 *
 * This class should be extended by systems that need to cache large amounts
 * of data and have it represented as an array to calling functions. These
 * arrays can become very large, so ArrayAccess is used to allow different
 * strategies to be used for caching internally (lazy loading, building caches
 * over time etc.). This can dramatically reduce the amount of data that needs
 * to be loaded from cache backends on each request, and memory usage from
 * static caches of that same data.
 *
 * Note that array_* functions do not work with ArrayAccess. Systems using
 * DrupalCacheArray should use this only internally. If providing API functions
 * that return the full array, this can be cached separately or returned
 * directly. However since DrupalCacheArray holds partial content by design, it
 * should be a normal PHP array or otherwise contain the full structure.
 *
 * Note also that due to limitations in PHP prior to 5.3.4, it is impossible to
 * write directly to the contents of nested arrays contained in this object.
 * Only writes to the top-level array elements are possible. So if you
 * previously had set $object['foo'] = array(1, 2, 'bar' => 'baz'), but later
 * want to change the value of 'bar' from 'baz' to 'foobar', you cannot do so
 * a targeted write like $object['foo']['bar'] = 'foobar'. Instead, you must
 * overwrite the entire top-level 'foo' array with the entire set of new
 * values: $object['foo'] = array(1, 2, 'bar' => 'foobar'). Due to this same
 * limitation, attempts to create references to any contained data, nested or
 * otherwise, will fail silently. So $var = &$object['foo'] will not throw an
 * error, and $var will be populated with the contents of $object['foo'], but
 * that data will be passed by value, not reference. For more information on
 * the PHP limitation, see the note in the official PHP documentation atá
 * http://php.net/manual/arrayaccess.offsetget.php on
 * ArrayAccess::offsetGet().
 *
 * By default, the class accounts for caches where calling functions might
 * request keys in the array that won't exist even after a cache rebuild. This
 * prevents situations where a cache rebuild would be triggered over and over
 * due to a 'missing' item. These cases are stored internally as a value of
 * NULL. This means that the offsetGet() and offsetExists() methods
 * must be overridden if caching an array where the top level values can
 * legitimately be NULL, and where $object->offsetExists() needs to correctly
 * return (equivalent to array_key_exists() vs. isset()). This should not
 * be necessary in the majority of cases.
 *
 * Classes extending this class must override at least the
 * resolveCacheMiss() method to have a working implementation.
 *
 * offsetSet() is not overridden by this class by default. In practice this
 * means that assigning an offset via arrayAccess will only apply while the
 * object is in scope and will not be written back to the persistent cache.
 * This follows a similar pattern to static vs. persistent caching in
 * procedural code. Extending classes may wish to alter this behaviour, for
 * example by overriding offsetSet() and adding an automatic call to persist().
 *
 * @see SchemaCache
 */
abstract class CacheArray implements ArrayAccess {

  /**
   * A cid to pass to cache()->set() and cache()->get().
   */
  protected $cid;

  /**
   * A bin to pass to cache()->set() and cache()->get().
   */
  protected $bin;

  /**
   * An array of keys to add to the cache at the end of the request.
   */
  protected $keysToPersist = array();

  /**
   * Storage for the data itself.
   */
  protected $storage = array();

  /**
   * Constructs a DrupalCacheArray object.
   *
   * @param $cid
   *   The cid for the array being cached.
   * @param $bin
   *   The bin to cache the array.
   */
  public function __construct($cid, $bin) {
    $this->cid = $cid;
    $this->bin = $bin;

    if ($cached = cache($bin)->get($this->cid)) {
     $this->storage = $cached->data;
    }
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($offset) {
    return $this->offsetGet($offset) !== NULL;
  }

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function offsetGet($offset) {
    if (isset($this->storage[$offset]) || array_key_exists($offset, $this->storage)) {
      return $this->storage[$offset];
    }
    else {
      return $this->resolveCacheMiss($offset);
    }
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($offset, $value) {
    $this->storage[$offset] = $value;
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($offset) {
    unset($this->storage[$offset]);
  }

  /**
   * Flags an offset value to be written to the persistent cache.
   *
   * If a value is assigned to a cache object with offsetSet(), by default it
   * will not be written to the persistent cache unless it is flagged with this
   * method. This allows items to be cached for the duration of a request,
   * without necessarily writing back to the persistent cache at the end.
   *
   * @param $offset
   *   The array offset that was request.
   * @param $persist
   *   Optional boolean to specify whether the offset should be persisted or
   *   not, defaults to TRUE. When called with $persist = FALSE the offset will
   *   be unflagged so that it will not written at the end of the request.
   */
  protected function persist($offset, $persist = TRUE) {
    $this->keysToPersist[$offset] = $persist;
  }

  /**
   * Resolves a cache miss.
   *
   * When an offset is not found in the object, this is treated as a cache
   * miss. This method allows classes implementing the interface to look up
   * the actual value and allow it to be cached.
   *
   * @param $offset
   *   The offset that was requested.
   *
   * @return
   *   The value of the offset, or NULL if no value was found.
   */
  abstract protected function resolveCacheMiss($offset);

  /**
   * Writes a value to the persistent cache immediately.
   *
   * @param $data
   *   The data to write to the persistent cache.
   * @param $lock
   *   Whether to acquire a lock before writing to cache.
   */
  protected function set($data, $lock = TRUE) {
    // Lock cache writes to help avoid stampedes.
    // To implement locking for cache misses, override __construct().
    $lock_name = $this->cid . ':' . $this->bin;
    if (!$lock || lock_acquire($lock_name)) {
      if ($cached = cache($this->bin)->get($this->cid)) {
        $data = $cached->data + $data;
      }
      cache($this->bin)->set($this->cid, $data);
      if ($lock) {
        lock_release($lock_name);
      }
    }
  }

  /**
   * Destructs the DrupalCacheArray object.
   */
  public function __destruct() {
    $data = array();
    foreach ($this->keysToPersist as $offset => $persist) {
      if ($persist) {
        $data[$offset] = $this->storage[$offset];
      }
    }
    if (!empty($data)) {
      $this->set($data);
    }
  }
}

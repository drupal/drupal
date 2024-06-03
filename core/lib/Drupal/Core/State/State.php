<?php

namespace Drupal\Core\State;

use Drupal\Core\Asset\AssetQueryString;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Site\Settings;

/**
 * Provides the state system using a key value store.
 */
class State extends CacheCollector implements StateInterface {

  /**
   * Information about all deprecated state, keyed by legacy state key.
   *
   * Each entry should be an array that defines the following keys:
   *   - 'replacement': The new name for the state.
   *   - 'message': The deprecation message to use for trigger_error().
   *
   * @var array
   */
  private static array $deprecatedState = [
    'system.css_js_query_string' => [
      'replacement' => AssetQueryString::STATE_KEY,
      'message' => 'The \'system.css_js_query_string\' state is deprecated in drupal:10.2.0. Use \Drupal\Core\Asset\AssetQueryStringInterface::get() and ::reset() instead. See https://www.drupal.org/node/3358337.',
    ],
  ];

  /**
   * The key value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * Constructs a State object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value store to use.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory, ?CacheBackendInterface $cache = NULL, ?LockBackendInterface $lock = NULL) {
    if (!$cache) {
      @trigger_error('Calling  ' . __METHOD__ . '() without the $cache argument is deprecated in drupal:10.3.0 and is required in drupal:11.0.0. See https://www.drupal.org/node/3177901', E_USER_DEPRECATED);
      $cache = \Drupal::cache('bootstrap');
    }
    if (!$lock) {
      @trigger_error('Calling  ' . __METHOD__ . '() without the $lock argument is deprecated in drupal:10.3.0 and is required in drupal:11.0.0. See https://www.drupal.org/node/3177901', E_USER_DEPRECATED);
      $lock = \Drupal::service('lock');
    }
    parent::__construct('state', $cache, $lock);
    $this->keyValueStore = $key_value_factory->get('state');

    // For backward compatibility, allow to opt-out of state caching, if cache
    // is not explicitly enabled, flag the cache as already loaded.
    if (Settings::get('state_cache') !== TRUE) {
      $this->cacheLoaded = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    // If the caller is asking for the value of a deprecated state, trigger a
    // deprecation message about it.
    if (isset(self::$deprecatedState[$key])) {
      // phpcs:ignore Drupal.Semantics.FunctionTriggerError
      @trigger_error(self::$deprecatedState[$key]['message'], E_USER_DEPRECATED);
      $key = self::$deprecatedState[$key]['replacement'];
    }
    return parent::get($key) ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    $value = $this->keyValueStore->get($key);
    $this->storage[$key] = $value;
    $this->persist($key);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    $values = [];
    foreach ($keys as $key) {
      $values[$key] = $this->get($key);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    if (isset(self::$deprecatedState[$key])) {
      // phpcs:ignore Drupal.Semantics.FunctionTriggerError
      @trigger_error(self::$deprecatedState[$key]['message'], E_USER_DEPRECATED);
      $key = self::$deprecatedState[$key]['replacement'];
    }
    $this->keyValueStore->set($key, $value);
    // If another request had a cache miss before this request, and also hasn't
    // written to cache yet, then it may already have read this value from the
    // database and could write that value to the cache to the end of the
    // request. To avoid this race condition, write to the cache immediately
    // after calling parent::set(). This allows the race condition detection in
    // CacheCollector::set() to work.
    parent::set($key, $value);
    $this->persist($key);
    static::updateCache();
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    $this->keyValueStore->setMultiple($data);
    foreach ($data as $key => $value) {
      parent::set($key, $value);
      $this->persist($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->keyValueStore->delete($key);
    parent::delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    $this->keyValueStore->deleteMultiple($keys);
    foreach ($keys as $key) {
      parent::delete($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->clear();
  }

  /**
   * {@inheritdoc}
   */
  protected function updateCache($lock = TRUE) {
    // For backward compatibility, allow to opt-out of state caching, if cache
    // is not explicitly enabled, there is no need to update it.
    if (Settings::get('state_cache') !== TRUE) {
      return;
    }
    parent::updateCache($lock);
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateCache() {
    // For backward compatibility, allow to opt-out of state caching, if cache
    // is not explicitly enabled, there is no need to invalidate it.
    if (Settings::get('state_cache') !== TRUE) {
      return;
    }
    parent::invalidateCache();
  }

}

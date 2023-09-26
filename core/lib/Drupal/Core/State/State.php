<?php

namespace Drupal\Core\State;

use Drupal\Core\Asset\AssetQueryString;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Provides the state system using a key value store.
 */
class State implements StateInterface {

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
   * Static state cache.
   *
   * @var array
   */
  protected $cache = [];

  /**
   * Constructs a State object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value store to use.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory) {
    $this->keyValueStore = $key_value_factory->get('state');
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
    $values = $this->getMultiple([$key]);
    return $values[$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    $values = [];
    $load = [];
    foreach ($keys as $key) {
      // Check if we have a value in the cache.
      if (isset($this->cache[$key])) {
        $values[$key] = $this->cache[$key];
      }
      // Load the value if we don't have an explicit NULL value.
      elseif (!array_key_exists($key, $this->cache)) {
        $load[] = $key;
      }
    }

    if ($load) {
      $loaded_values = $this->keyValueStore->getMultiple($load);
      foreach ($load as $key) {
        // If we find a value, even one that is NULL, add it to the cache and
        // return it.
        if (\array_key_exists($key, $loaded_values)) {
          $values[$key] = $loaded_values[$key];
          $this->cache[$key] = $loaded_values[$key];
        }
        else {
          $this->cache[$key] = NULL;
        }
      }
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
    $this->cache[$key] = $value;
    $this->keyValueStore->set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    foreach ($data as $key => $value) {
      $this->cache[$key] = $value;
    }
    $this->keyValueStore->setMultiple($data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->deleteMultiple([$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    foreach ($keys as $key) {
      unset($this->cache[$key]);
    }
    $this->keyValueStore->deleteMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->cache = [];
  }

}

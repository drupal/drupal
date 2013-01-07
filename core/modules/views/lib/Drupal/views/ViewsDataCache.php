<?php

/**
 * @file
 * Contains \Drupal\views\ViewsDataCache.
 */

namespace Drupal\views;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class to manage and lazy load cached views data.
 */
class ViewsDataCache {

  /**
   * The base cache ID to use.
   *
   * @var string
   */
  protected $baseCid = 'views_data';

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Storage for the data itself.
   *
   * @var array
   */
  protected $storage = array();

  /**
   * The configuration factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The current language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Whether the data has been fully loaded in this request.
   *
   * @var bool
   */
  protected $fullyLoaded = FALSE;

  /**
   * Whether or not to skip data caching and rebuild data each time.
   *
   * @var bool
   */
  protected $skipCache;

  /**
   * Whether the cache should be rebuilt. This is set when getData() is called.
   *
   * @var bool
   */
  protected $rebuildCache;

  public function __construct(CacheBackendInterface $cache_backend, ConfigFactory $config) {
    $this->config = $config;
    $this->cacheBackend = $cache_backend;

    $this->langcode = language(LANGUAGE_TYPE_INTERFACE)->langcode;
    $this->skipCache = $this->config->get('views.settings')->get('skip_cache');
  }

  /**
   * Gets cached data for a particular key, or rebuilds if necessary.
   *
   * @param string|null $key
   *   The key of the cache entry to retrieve. Defaults to NULL.
   *
   * @return array $data
   *   The cached data.
   */
  public function get($key = NULL) {
    if ($key) {
      if (!isset($this->storage[$key])) {
        $cid = $this->baseCid . ':' . $key;
        $data = $this->cacheGet($cid);
        if (!empty($data->data)) {
          $this->storage[$key] = $data->data;
        }
        else {
          // No cache entry, rebuild.
          $this->storage = $this->getData();
          $this->fullyLoaded = TRUE;
        }
      }
      if (isset($this->storage[$key])) {
        return $this->storage[$key];
      }
      // If the key is invalid, return an empty array.
      return array();
    }
    else {
      if (!$this->fullyLoaded) {
        $data = $this->cacheGet($this->baseCid);
        if (!empty($data->data)) {
          $this->storage = $data->data;
        }
        else {
          $this->storage = $this->getData();
        }
        $this->fullyLoaded = TRUE;
      }
    }

    return $this->storage;
  }

  /**
   * Sets the data in the cache backend for a cache key.
   *
   * @param string $key
   *   The cache key to set.
   * @param mixed $value
   *   The value to set for this key.
   */
  public function set($key, $value) {
    if ($this->skipCache) {
      return FALSE;
    }

    $key .= ':' . $this->langcode;

    $this->cacheBackend->set($key, $value);
  }

  /**
   * Gets data from the cache backend.
   *
   * @param string $cid
   *   The cache ID to return.
   *
   * @return mixed
   *   The cached data, if any. This will immediately return FALSE if the
   *   $skipCache property is TRUE.
   */
  protected function cacheGet($cid) {
    if ($this->skipCache) {
      return FALSE;
    }

    $cid .= ':' . $this->langcode;

    return $this->cacheBackend->get($cid);
  }

  /**
   * Gets all data invoked by hook_views_data().
   *
   * @return array
   *   An array of all data.
   */
  protected function getData() {
    $data = module_invoke_all('views_data');
    drupal_alter('views_data', $data);

    $this->processEntityTypes($data);

    $this->rebuildCache = TRUE;

    return $data;
  }

  /**
   * Links tables with 'entity type' to respective generic entity-type tables.
   *
   * @param array $data
   *   The array of data to alter entity data for, passed by reference.
   */
  protected function processEntityTypes(array &$data) {
    foreach ($data as $table_name => $table_info) {
      // Add in a join from the entity-table if an entity-type is given.
      if (!empty($table_info['table']['entity type'])) {
        $entity_table = 'views_entity_' . $table_info['table']['entity type'];

        $data[$entity_table]['table']['join'][$table_name] = array(
          'left_table' => $table_name,
        );
        $data[$entity_table]['table']['entity type'] = $table_info['table']['entity type'];
        // Copy over the default table group if we have none yet.
        if (!empty($table_info['table']['group']) && empty($data[$entity_table]['table']['group'])) {
          $data[$entity_table]['table']['group'] = $table_info['table']['group'];
        }
      }
    }
  }

  /**
   * Destructs the ViewDataCache object.
   */
  public function __destruct() {
    if ($this->rebuildCache && !empty($this->storage)) {
      // Keep a record with all data.
      $this->set($this->baseCid, $this->storage);
      // Save data in seperate cache entries.
      foreach ($this->storage as $table => $data) {
        $cid = $this->baseCid . ':' . $table;
        $this->set($cid, $data);
      }
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\views\ViewsDataCache.
 */

namespace Drupal\views;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\DestructableInterface;

/**
 * Class to manage and lazy load cached views data.
 */
class ViewsDataCache implements DestructableInterface {

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
   * An array of requested tables.
   *
   * @var array
   */
  protected $requestedTables = array();

  /**
   * Whether the data has been fully loaded in this request.
   *
   * @var bool
   */
  protected $fullyLoaded = FALSE;

  /**
   * Whether views data has been rebuilt. This is set when getData() doesn't
   * return anything from cache.
   *
   * @var bool
   */
  protected $rebuildAll = FALSE;

  /**
   * Whether or not to skip data caching and rebuild data each time.
   *
   * @var bool
   */
  protected $skipCache = FALSE;

  /**
   * The current language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Stores a module manager to invoke hooks.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs this ViewsDataCache object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The configuration factory object to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler class to use for invoking hooks.
   */
  public function __construct(CacheBackendInterface $cache_backend, ConfigFactory $config, ModuleHandlerInterface $module_handler) {
    $this->cacheBackend = $cache_backend;
    $this->moduleHandler = $module_handler;

    $this->langcode = language(LANGUAGE_TYPE_INTERFACE)->langcode;
    $this->skipCache = $config->get('views.settings')->get('skip_cache');
  }

  /**
   * Gets data for a particular table, or all tables.
   *
   * @param string|null $key
   *   The key of the cache entry to retrieve. Defaults to NULL, this will
   *   return all table data.
   *
   * @return array $data
   *   An array of table data.
   */
  public function get($key = NULL) {
    if ($key) {
      $from_cache = FALSE;
      if (!isset($this->storage[$key])) {
        // Prepare a cache ID.
        $cid = $this->baseCid . ':' . $key;

        if ($data = $this->cacheGet($cid)) {
          $this->storage[$key] = $data->data;
          $from_cache = TRUE;
        }
        // If there is no cached entry and data is not already fully loaded,
        // rebuild. This will stop requests for invalid tables calling getData.
        elseif (!$this->fullyLoaded) {
          $this->storage = $this->getData();
        }
      }

      if (isset($this->storage[$key])) {
        if (!$from_cache) {
          // Add this table to a list of requested tables, as it's table cache
          // entry was not found.
          array_push($this->requestedTables, $key);
        }

        return $this->storage[$key];
      }

      // If the key is invalid, return an empty array.
      return array();
    }
    else {
      if (!$this->fullyLoaded) {
        $this->storage = $this->getData();
      }
    }

    return $this->storage;
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

    return $this->cacheBackend->get($this->prepareCid($cid));
  }

  /**
   * Prepares the cache ID by appending a language code.
   *
   * @param string $cid
   *   The cache ID to prepare.
   *
   * @return string
   *   The prepared cache ID.
   */
  protected function prepareCid($cid) {
    return $cid . ':' . $this->langcode;
  }

  /**
   * Gets all data invoked by hook_views_data().
   *
   * This is requested from the cache before being rebuilt.
   *
   * @return array
   *   An array of all data.
   */
  protected function getData() {
    $this->fullyLoaded = TRUE;

    if ($data = $this->cacheGet($this->baseCid)) {
      return $data->data;
    }
    else {
      $data = $this->moduleHandler->invokeAll('views_data');
      $this->moduleHandler->alter('views_data', $data);

      $this->processEntityTypes($data);

      // Set as TRUE, so all table data will be cached.
      $this->rebuildAll = TRUE;

      return $data;
    }
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
   * Fetches a list of all base tables available.
   *
   * @return array
   *   An array of base table data keyed by table name. Each item contains the
   *   following keys:
   *     - title: The title label for the base table.
   *     - help: The help text for the base table.
   *     - weight: The weight of the base table.
   */
  public function fetchBaseTables() {
    $tables = array();

    foreach ($this->get() as $table => $info) {
      if (!empty($info['table']['base'])) {
        $tables[$table] = array(
          'title' => $info['table']['base']['title'],
          'help' => !empty($info['table']['base']['help']) ? $info['table']['base']['help'] : '',
          'weight' => !empty($info['table']['base']['weight']) ? $info['table']['base']['weight'] : 0,
        );
      }
    }

    // Sorts by the 'weight' and then by 'title' element.
    uasort($tables, function ($a, $b) {
      if ($a['weight'] != $b['weight']) {
        return $a['weight'] < $b['weight'] ? -1 : 1;
      }
      if ($a['title'] != $b['title']) {
        return $a['title'] < $b['title'] ? -1 : 1;
      }
      return 0;
    });

    return $tables;
  }

  /**
   * Implements \Drupal\Core\DestructableInterface::destruct().
   */
  public function destruct() {
    if (!empty($this->storage) && !$this->skipCache) {
      if ($this->rebuildAll) {
        // Keep a record with all data.
        $this->cacheBackend->set($this->prepareCid($this->baseCid), $this->storage);
      }

      // Save data in seperate, per table cache entries.
      foreach ($this->requestedTables as $table) {
        $cid = $this->baseCid . ':' . $table;
        $this->cacheBackend->set($this->prepareCid($cid), $this->storage[$table]);
      }
    }
  }

  /**
   * Clears the class storage and cache.
   */
  public function clear() {
    $this->storage = array();
    $this->fullyLoaded = FALSE;
    $this->cacheBackend->deleteAll();
  }
}

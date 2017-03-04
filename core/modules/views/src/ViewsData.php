<?php

namespace Drupal\views;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class to manage and lazy load cached views data.
 *
 * If a table is requested and cannot be loaded from cache, all data is then
 * requested from cache. A table-specific cache entry will then be created for
 * the requested table based on this cached data. Table data is only rebuilt
 * when no cache entry for all table data can be retrieved.
 */
class ViewsData {

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
   * Table data storage.
   *
   * This is used for explicitly requested tables.
   *
   * @var array
   */
  protected $storage = [];

  /**
   * All table storage data loaded from cache.
   *
   * This is used when all data has been loaded from the cache to prevent
   * further cache get calls when rebuilding all data or for single tables.
   *
   * @var array
   */
  protected $allStorage = [];

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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs this ViewsData object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler class to use for invoking hooks.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(CacheBackendInterface $cache_backend, ConfigFactoryInterface $config, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    $this->cacheBackend = $cache_backend;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;

    $this->langcode = $this->languageManager->getCurrentLanguage()->getId();
    $this->skipCache = $config->get('views.settings')->get('skip_cache');
  }

  /**
   * Gets all table data.
   *
   * @see https://www.drupal.org/node/2723553
   *
   * @return array
   *   An array of table data.
   */
  public function getAll() {
    if (!$this->fullyLoaded) {
      $this->allStorage = $this->getData();
    }

    // Set storage from allStorage outside of the fullyLoaded check to prevent
    // cache calls on requests that have requested all data to get a single
    // tables data. Make sure $this->storage is populated in this case.
    $this->storage = $this->allStorage;
    return $this->allStorage;
  }

  /**
   * Gets data for a particular table, or all tables.
   *
   * @param string|null $key
   *   The key of the cache entry to retrieve. Defaults to NULL, this will
   *   return all table data.
   *
   * @deprecated NULL $key deprecated in Drupal 8.2.x and will be removed in
   * 9.0.0. Use getAll() instead.
   *
   * @see https://www.drupal.org/node/2723553
   *
   * @return array
   *   An array of table data.
   */
  public function get($key = NULL) {
    if (!$key) {
      return $this->getAll();
    }
    if (!isset($this->storage[$key])) {
      // Prepare a cache ID for get and set.
      $cid = $this->baseCid . ':' . $key;
      $from_cache = FALSE;

      if ($data = $this->cacheGet($cid)) {
        $this->storage[$key] = $data->data;
        $from_cache = TRUE;
      }
      // If there is no cached entry and data is not already fully loaded,
      // rebuild. This will stop requests for invalid tables calling getData.
      elseif (!$this->fullyLoaded) {
        $this->allStorage = $this->getData();
      }

      if (!$from_cache) {
        if (!isset($this->allStorage[$key])) {
          // Write an empty cache entry if no information for that table
          // exists to avoid repeated cache get calls for this table and
          // prevent loading all tables unnecessarily.
          $this->storage[$key] = [];
          $this->allStorage[$key] = [];
        }
        else {
          $this->storage[$key] = $this->allStorage[$key];
        }

        // Create a cache entry for the requested table.
        $this->cacheSet($cid, $this->allStorage[$key]);
      }
    }
    return $this->storage[$key];
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
   * Sets data to the cache backend.
   *
   * @param string $cid
   *   The cache ID to set.
   * @param mixed $data
   *   The data that will be cached.
   */
  protected function cacheSet($cid, $data) {
    return $this->cacheBackend->set($this->prepareCid($cid), $data, Cache::PERMANENT, ['views_data', 'config:core.extension']);
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
      $modules = $this->moduleHandler->getImplementations('views_data');
      $data = [];
      foreach ($modules as $module) {
        $views_data = $this->moduleHandler->invoke($module, 'views_data');
        // Set the provider key for each base table.
        foreach ($views_data as &$table) {
          if (isset($table['table']) && !isset($table['table']['provider'])) {
            $table['table']['provider'] = $module;
          }
        }
        $data = NestedArray::mergeDeep($data, $views_data);
      }
      $this->moduleHandler->alter('views_data', $data);

      $this->processEntityTypes($data);

      // Keep a record with all data.
      $this->cacheSet($this->baseCid, $data);

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

        $data[$entity_table]['table']['join'][$table_name] = [
          'left_table' => $table_name,
        ];
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
    $tables = [];

    foreach ($this->get() as $table => $info) {
      if (!empty($info['table']['base'])) {
        $tables[$table] = [
          'title' => $info['table']['base']['title'],
          'help' => !empty($info['table']['base']['help']) ? $info['table']['base']['help'] : '',
          'weight' => !empty($info['table']['base']['weight']) ? $info['table']['base']['weight'] : 0,
        ];
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
   * Clears the class storage and cache.
   */
  public function clear() {
    $this->storage = [];
    $this->allStorage = [];
    $this->fullyLoaded = FALSE;
    Cache::invalidateTags(['views_data']);
  }

}

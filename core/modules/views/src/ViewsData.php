<?php

namespace Drupal\views;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
   * The cache tags to apply to all cache entries.
   */
  const array CACHE_TAGS = [
    'views_data',
    'config:core.extension',
  ];

  /**
   * The base cache ID to use.
   *
   * @var string
   */
  protected $baseCid = 'views_data';

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
   * Flag that indicates whether views data are currently being loaded.
   */
  protected bool $loading = FALSE;

  /**
   * The current language code.
   *
   * @var string
   */
  protected $langcode;

  public function __construct(
    #[Autowire(service: 'cache.default')]
    protected CacheBackendInterface $cacheBackend,
    protected ModuleHandlerInterface $moduleHandler,
    protected LanguageManagerInterface $languageManager,
    #[Autowire(service: 'cache.discovery')]
    protected CacheBackendInterface $chainedFastBackend,
  ) {
    $this->langcode = $this->languageManager->getCurrentLanguage()->getId();
  }

  /**
   * Gets all table data.
   *
   * @return array
   *   An array of table data.
   */
  public function getAll() {
    if ($this->loading && \Fiber::getCurrent() !== NULL) {
      // The loading flag is set in ::getData(). If 'loading' is TRUE when
      // entering here, that indicates a separate fiber started loading views
      // data but has not completed. Suspend this fiber once to give the other
      // fiber a chance to complete loading.
      \Fiber::suspend();
    }

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
   * Gets data for a particular table.
   *
   * @param string $key
   *   The key of the cache entry to retrieve.
   *
   * @return array
   *   An array of table data.
   */
  public function get($key) {
    if (!$key) {
      throw new \InvalidArgumentException('A valid cache entry key is required. Use getAll() to get all table data.');
    }

    if ($this->loading && \Fiber::getCurrent() !== NULL) {
      // The loading flag is set in ::getData(). If 'loading' is TRUE when
      // entering here, that indicates a separate fiber started loading views
      // data but has not completed. Suspend this fiber once to give the other
      // fiber a chance to complete loading.
      \Fiber::suspend();
    }

    if (!isset($this->storage[$key])) {
      // Prepare a cache ID for get and set.
      $cid = $this->prepareCid($this->baseCid . ':' . $key);
      $from_cache = FALSE;

      if ($data = $this->chainedFastBackend->get($cid)) {
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
        $this->chainedFastBackend->set($cid, $this->allStorage[$key], Cache::PERMANENT, static::CACHE_TAGS);
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
   *   The cached data.
   *
   * @deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. There is no
   * replacement.
   * @see https://www.drupal.org/project/drupal/issues/3587797
   */
  protected function cacheGet($cid) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/project/drupal/issues/3587797', E_USER_DEPRECATED);
    return $this->cacheBackend->get($this->prepareCid($cid));
  }

  /**
   * Sets data to the cache backend.
   *
   * @param string $cid
   *   The cache ID to set.
   * @param mixed $data
   *   The data that will be cached.
   *
   * @deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. There is no
   * replacement.
   * @see https://www.drupal.org/project/drupal/issues/3587797
   */
  protected function cacheSet($cid, $data) {
    @trigger_error(__METHOD__ . ' is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/project/drupal/issues/3587797', E_USER_DEPRECATED);
    return $this->cacheBackend->set($this->prepareCid($cid), $data, Cache::PERMANENT, [
      'views_data',
      'config:core.extension',
    ]);
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
    $cid = $this->prepareCid($this->baseCid);
    if ($cache = $this->cacheBackend->get($cid)) {
      $data = $cache->data;
    }
    else {
      // Set the loading flag in case this is running in a fiber and gets
      // suspended before the views data is fully loaded. Other code that calls
      // this method and runs in a separate fiber can check the loading flag
      // and suspend its fiber once to allow the original fiber a chance to
      // finish loading.
      $this->loading = TRUE;
      $data = [];
      $this->moduleHandler->invokeAllWith('views_data', function (callable $hook, string $module) use (&$data) {
        $views_data = $hook();
        // Set the provider key for each base table.
        foreach ($views_data as &$table) {
          if (isset($table['table']) && !isset($table['table']['provider'])) {
            $table['table']['provider'] = $module;
          }
        }
        $data = NestedArray::mergeDeep($data, $views_data);
      });
      $this->moduleHandler->alter('views_data', $data);

      $this->processEntityTypes($data);

      // Keep a record with all data.
      $this->cacheBackend->set($cid, $data, Cache::PERMANENT, static::CACHE_TAGS);
    }

    $this->fullyLoaded = TRUE;
    $this->loading = FALSE;

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

    foreach ($this->getAll() as $table => $info) {
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
        return $a['weight'] <=> $b['weight'];
      }
      return $a['title'] <=> $b['title'];
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

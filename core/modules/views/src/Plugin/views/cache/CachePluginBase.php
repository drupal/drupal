<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\cache\CachePluginBase.
 */

namespace Drupal\views\Plugin\views\cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\Core\Database\Query\Select;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @defgroup views_cache_plugins Views cache plugins
 * @{
 * Plugins to handle Views caches.
 *
 * Cache plugins control how caching is done in Views.
 *
 * Cache plugins extend \Drupal\views\Plugin\views\cache\CachePluginBase.
 * They must be annotated with \Drupal\views\Annotation\ViewsCache
 * annotation, and must be in namespace directory Plugin\views\cache.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * The base plugin to handle caching.
 */
abstract class CachePluginBase extends PluginBase {

  /**
   * Contains all data that should be written/read from cache.
   */
  var $storage = array();

  /**
   * Which cache bin to store query results in.
   *
   * @var string
   */
  protected $resultsBin = 'data';

  /**
   * Stores the cache ID used for the results cache.
   *
   * The cache ID is stored in generateResultsKey() got executed.
   *
   * @var string
   *
   * @see \Drupal\views\Plugin\views\cache\CachePluginBase::generateResultsKey()
   */
  protected $resultsKey;

  /**
   * The HTML renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The render cache service.
   *
   * @var \Drupal\Core\Render\RenderCacheInterface
   */
  protected $renderCache;

  /**
   * Constructs a CachePluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The HTML renderer.
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, RenderCacheInterface $render_cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->renderer = $renderer;
    $this->renderCache = $render_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('render_cache')
    );
  }

  /**
   * Returns the resultsKey property.
   *
   * @return string
   *   The resultsKey property.
   */
  public function getResultsKey() {
    return $this->resultsKey;
  }

  /**
   * Return a string to display as the clickable title for the
   * access control.
   */
  public function summaryTitle() {
    return $this->t('Unknown');
  }

  /**
   * Determine the expiration time of the cache type, or NULL if no expire.
   *
   * Plugins must override this to implement expiration.
   *
   * @param $type
   *   The cache type, either 'query', 'result'.
   */
  protected function cacheExpire($type) {
  }

  /**
   * Determine expiration time in the cache table of the cache type
   * or CACHE_PERMANENT if item shouldn't be removed automatically from cache.
   *
   * Plugins must override this to implement expiration in the cache table.
   *
   * @param $type
   *   The cache type, either 'query', 'result'.
   */
  protected function cacheSetMaxAge($type) {
    return Cache::PERMANENT;
  }

  /**
   * Save data to the cache.
   *
   * A plugin should override this to provide specialized caching behavior.
   */
  public function cacheSet($type) {
    switch ($type) {
      case 'query':
        // Not supported currently, but this is certainly where we'd put it.
        break;
      case 'results':
        $data = array(
          'result' => $this->prepareViewResult($this->view->result),
          'total_rows' => isset($this->view->total_rows) ? $this->view->total_rows : 0,
          'current_page' => $this->view->getCurrentPage(),
        );
        $expire = ($this->cacheSetMaxAge($type) === Cache::PERMANENT) ? Cache::PERMANENT : (int) $this->view->getRequest()->server->get('REQUEST_TIME') + $this->cacheSetMaxAge($type);
        \Drupal::cache($this->resultsBin)->set($this->generateResultsKey(), $data, $expire, $this->getCacheTags());
        break;
    }
  }

  /**
   * Retrieve data from the cache.
   *
   * A plugin should override this to provide specialized caching behavior.
   */
  public function cacheGet($type) {
    $cutoff = $this->cacheExpire($type);
    switch ($type) {
      case 'query':
        // Not supported currently, but this is certainly where we'd put it.
        return FALSE;
      case 'results':
        // Values to set: $view->result, $view->total_rows, $view->execute_time,
        // $view->current_page.
        if ($cache = \Drupal::cache($this->resultsBin)->get($this->generateResultsKey())) {
          if (!$cutoff || $cache->created > $cutoff) {
            $this->view->result = $cache->data['result'];
            // Load entities for each result.
            $this->view->query->loadEntities($this->view->result);
            $this->view->total_rows = $cache->data['total_rows'];
            $this->view->setCurrentPage($cache->data['current_page'], TRUE);
            $this->view->execute_time = 0;
            return TRUE;
          }
        }
        return FALSE;
    }
  }

  /**
   * Clear out cached data for a view.
   */
  public function cacheFlush() {
    Cache::invalidateTags($this->view->storage->getCacheTags());
  }

  /**
   * Post process any rendered data.
   *
   * This can be valuable to be able to cache a view and still have some level of
   * dynamic output. In an ideal world, the actual output will include HTML
   * comment based tokens, and then the post process can replace those tokens.
   *
   * Example usage. If it is known that the view is a node view and that the
   * primary field will be a nid, you can do something like this:
   *
   * <!--post-FIELD-NID-->
   *
   * And then in the post render, create an array with the text that should
   * go there:
   *
   * strtr($output, array('<!--post-FIELD-1-->', 'output for FIELD of nid 1');
   *
   * All of the cached result data will be available in $view->result, as well,
   * so all ids used in the query should be discoverable.
   */
  public function postRender(&$output) { }

  /**
   * Calculates and sets a cache ID used for the result cache.
   *
   * @return string
   *   The generated cache ID.
   */
  public function generateResultsKey() {
    if (!isset($this->resultsKey)) {
      $build_info = $this->view->build_info;

      foreach (array('query', 'count_query') as $index) {
        // If the default query back-end is used generate SQL query strings from
        // the query objects.
        if ($build_info[$index] instanceof Select) {
          $query = clone $build_info[$index];
          $query->preExecute();
          $build_info[$index] = array(
            'query' => (string)$query,
            'arguments' => $query->getArguments(),
          );
        }
      }

      $key_data = [
        'build_info' => $build_info,
      ];
      // @todo https://www.drupal.org/node/2433591 might solve it to not require
      //    the pager information here.
      $key_data['pager'] = [
        'page' => $this->view->getCurrentPage(),
        'items_per_page' => $this->view->getItemsPerPage(),
        'offset' => $this->view->getOffset(),
      ];
      $key_data += \Drupal::service('cache_contexts_manager')->convertTokensToKeys($this->displayHandler->getCacheMetadata()['contexts']);

      $this->resultsKey = $this->view->storage->id() . ':' . $this->displayHandler->display['id'] . ':results:' . hash('sha256', serialize($key_data));
    }

    return $this->resultsKey;
  }

  /**
   * Gets an array of cache tags for the current view.
   *
   * @return string[]
   *   An array of cache tags based on the current view.
   */
  public function getCacheTags() {
    $tags = $this->view->storage->getCacheTags();

    // The list cache tags for the entity types listed in this view.
    $entity_information = $this->view->getQuery()->getEntityTableInfo();

    if (!empty($entity_information)) {
      // Add the list cache tags for each entity type used by this view.
      foreach ($entity_information as $table => $metadata) {
        $tags = Cache::mergeTags($tags, \Drupal::entityManager()->getDefinition($metadata['entity_type'])->getListCacheTags());
      }
    }

    $tags = Cache::mergeTags($tags, $this->view->getQuery()->getCacheTags());

    return $tags;
  }

  /**
   * Gets the max age for the current view.
   *
   * @return int
   */
  public function getCacheMaxAge() {
    $max_age = $this->getDefaultCacheMaxAge();
    $max_age = Cache::mergeMaxAges($max_age, $this->view->getQuery()->getCacheMaxAge());
    return $max_age;
  }

  /**
   * Returns the default cache max age.
   */
  protected function getDefaultCacheMaxAge() {
    // The default cache backend is not caching anything.
    return 0;
  }

  /**
   * Prepares the view result before putting it into cache.
   *
   * @param \Drupal\views\ResultRow[] $result
   *   The result containing loaded entities.
   *
   * @return \Drupal\views\ResultRow[] $result
   *   The result without loaded entities.
   */
  protected function prepareViewResult(array $result) {
    $return = [];

    // Clone each row object and remove any loaded entities, to keep the
    // original result rows intact.
    foreach ($result as $key => $row) {
      $clone = clone $row;
      $clone->resetEntityData();
      $return[$key] = $clone;
    }

    return $return;
  }

  /**
   * Alters the cache metadata of a display upon saving a view.
   *
   * @param bool $is_cacheable
   *   Whether the display is cacheable.
   * @param string[] $cache_contexts
   *   The cache contexts the display varies by.
   */
  public function alterCacheMetadata(&$is_cacheable, array &$cache_contexts) {
  }

  /**
   * Returns the row cache tags.
   *
   * @param ResultRow $row
   *   A result row.
   *
   * @return string[]
   *   The row cache tags.
   */
  public function getRowCacheTags(ResultRow $row) {
    $tags = !empty($row->_entity) ? $row->_entity->getCacheTags() : [];

    if (!empty($row->_relationship_entities)) {
      foreach ($row->_relationship_entities as $entity) {
        $tags = Cache::mergeTags($tags, $entity->getCacheTags());
      }
    }

    return $tags;
  }

  /**
   * Returns the row cache keys.
   *
   * @param \Drupal\views\ResultRow $row
   *   A result row.
   *
   * @return string[]
   *   The row cache keys.
   */
  public function getRowCacheKeys(ResultRow $row) {
    return [
      'views',
      'fields',
      $this->view->id(),
      $this->view->current_display,
      $this->getRowId($row),
    ];
  }

  /**
   * Returns a unique identifier for the specified row.
   *
   * @param \Drupal\views\ResultRow $row
   *   A result row.
   *
   * @return string
   *   The row identifier.
   */
  public function getRowId(ResultRow $row) {
    // Here we compute a unique identifier for the row by computing the hash of
    // its data. We exclude the current index, since the same row could have a
    // different result index depending on the user permissions. We exclude also
    // entity data, since serializing entity objects is very expensive. Instead
    // we include entity cache tags, which are enough to identify all the
    // entities associated with the row.
    $row_data = array_diff_key((array) $row, array_flip(['index', '_entity', '_relationship_entities'])) + $this->getRowCacheTags($row);

    // This ensures that we get a unique identifier taking field handler access
    // into account: users having access to different sets of fields will get
    // different row identifiers.
    $field_ids = array_keys($this->view->field);
    $row_data += array_flip($field_ids);

    // Finally we compute a hash of row data and return it as row identifier.
    return hash('sha256', serialize($row_data));
  }

}

/**
 * @}
 */

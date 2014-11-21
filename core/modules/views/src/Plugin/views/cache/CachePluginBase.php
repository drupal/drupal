<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\cache\CachePluginBase.
 */

namespace Drupal\views\Plugin\views\cache;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\Core\Database\Query\Select;

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
   * Which cache bin to store the rendered output in.
   *
   * @var string
   */
  protected $outputBin = 'render';

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
   * Stores the cache ID used for the output cache, once generateOutputKey() got
   * executed.
   *
   * @var string
   *
   * @see \Drupal\views\Plugin\views\cache\CachePluginBase::generateOutputKey()
   */
  protected $outputKey;

  /**
   * Returns the outputKey property.
   *
   * @return string
   *   The outputKey property.
   */
  public function getOutputKey() {
    return $this->outputKey;
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
   *   The cache type, either 'query', 'result' or 'output'.
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
   *   The cache type, either 'query', 'result' or 'output'.
   */
  protected function cacheSetExpire($type) {
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
          'result' => $this->view->result,
          'total_rows' => isset($this->view->total_rows) ? $this->view->total_rows : 0,
          'current_page' => $this->view->getCurrentPage(),
        );
        \Drupal::cache($this->resultsBin)->set($this->generateResultsKey(), $data, $this->cacheSetExpire($type), $this->getCacheTags());
        break;
      case 'output':
        $this->storage['output'] = drupal_render($this->view->display_handler->output);
        $this->gatherRenderMetadata($this->view->display_handler->output);
        \Drupal::cache($this->outputBin)->set($this->generateOutputKey(), $this->storage, $this->cacheSetExpire($type), $this->getCacheTags());
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
            $this->view->total_rows = $cache->data['total_rows'];
            $this->view->setCurrentPage($cache->data['current_page']);
            $this->view->execute_time = 0;
            return TRUE;
          }
        }
        return FALSE;
      case 'output':
        if ($cache = \Drupal::cache($this->outputBin)->get($this->generateOutputKey())) {
          if (!$cutoff || $cache->created > $cutoff) {
            $this->storage = $cache->data;

            $this->restoreRenderMetadata();
            $this->view->display_handler->output = array(
              '#attached' => &$this->view->element['#attached'],
              '#cache' => [
                'tags' => &$this->view->element['#cache']['tags'],
              ],
              '#post_render_cache' => &$this->view->element['#post_render_cache'],
              '#markup' => $cache->data['output'],
            );

            return TRUE;
          }
        }
        return FALSE;
    }
  }

  /**
   * Clear out cached data for a view.
   *
   * We're just going to nuke anything related to the view, regardless of display,
   * to be sure that we catch everything. Maybe that's a bad idea.
   */
  public function cacheFlush() {
    $id = $this->view->storage->id();
    Cache::invalidateTags(array('view:' . $id));
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
   * Start caching the html head.
   */
  public function cacheStart() { }

  /**
   * Gather bubbleable render metadata from the render array.
   *
   * @param array $render_array
   *   The view render array to collect data from.
   */
  protected function gatherRenderMetadata(array $render_array = []) {
    $this->storage['attachments'] = $render_array['#attached'];
    $this->storage['postRenderCache'] = $render_array['#post_render_cache'];
    $this->storage['cacheTags'] = $render_array['#cache']['tags'];
  }

  /**
   * Restore bubbleable render metadata.
   */
  public function restoreRenderMetadata() {
    $this->view->element['#attached'] = drupal_merge_attached($this->view->element['#attached'], $this->storage['attachments']);
    $this->view->element['#cache']['tags'] = Cache::mergeTags(isset($this->view->element['#cache']['tags']) ? $this->view->element['#cache']['tags'] : [], $this->storage['cacheTags']);
    $this->view->element['#post_render_cache'] = NestedArray::mergeDeep(isset($this->view->element['#post_render_cache']) ? $this->view->element['#post_render_cache'] : [], $this->storage['postRenderCache']);
  }

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
          $build_info[$index] = (string)$query;
        }
      }
      $user = \Drupal::currentUser();
      $key_data = array(
        'build_info' => $build_info,
        'roles' => $user->getRoles(),
        'super-user' => $user->id() == 1, // special caching for super user.
        'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
        'base_url' => $GLOBALS['base_url'],
      );
      foreach (array('exposed_info', 'page', 'sort', 'order', 'items_per_page', 'offset') as $key) {
        if ($this->view->getRequest()->query->has($key)) {
          $key_data[$key] = $this->view->getRequest()->query->get($key);
        }
      }

      $this->resultsKey = $this->view->storage->id() . ':' . $this->displayHandler->display['id'] . ':results:' . hash('sha256', serialize($key_data));
    }

    return $this->resultsKey;
  }

  /**
   * Calculates and sets a cache ID used for the output cache.
   *
   * @return string
   *   The generated cache ID.
   */
  public function generateOutputKey() {
    if (!isset($this->outputKey)) {
      $user = \Drupal::currentUser();
      $key_data = array(
        'result' => $this->view->result,
        'roles' => $user->getRoles(),
        'super-user' => $user->id() == 1, // special caching for super user.
        'theme' => \Drupal::theme()->getActiveTheme()->getName(),
        'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
        'base_url' => $GLOBALS['base_url'],
      );

      $this->outputKey = $this->view->storage->id() . ':' . $this->displayHandler->display['id'] . ':output:' . hash('sha256', serialize($key_data));
    }

    return $this->outputKey;
  }

  /**
   * Gets an array of cache tags for the current view.
   *
   * @return array
   *   An array fo cache tags based on the current view.
   */
  protected function getCacheTags() {
    $id = $this->view->storage->id();
    $tags = array('view:' . $id);

    $entity_information = $this->view->query->getEntityTableInfo();

    if (!empty($entity_information)) {
      // Add the list cache tags for each entity type used by this view.
      foreach (array_keys($entity_information) as $entity_type) {
        $tags = Cache::mergeTags($tags, \Drupal::entityManager()->getDefinition($entity_type)->getListCacheTags());
      }
    }

    return $tags;
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

}

/**
 * @}
 */

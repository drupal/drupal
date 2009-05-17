<?php

/**
 * @file
 * Definition of views_plugin_cache.
 */

/**
 * @defgroup views_cache_plugins Views cache plugins
 * @{
 * @todo.
 *
 * @see hook_views_plugins()
 */

/**
 * The base plugin to handle caching.
 */
class views_plugin_cache extends views_plugin {
  /**
   * Contains all data that should be written/read from cache.
   */
  var $storage = array();

  /**
   * What table to store data in.
   */
  var $table = 'cache_views_data';

  /**
   * Initialize the plugin.
   *
   * @param $view
   *   The view object.
   * @param $display
   *   The display handler.
   */
  function init(&$view, &$display) {
    $this->view = &$view;
    $this->display = &$display;

    if (is_object($display->handler)) {
      $options = $display->handler->get_option('cache');
      // Overlay incoming options on top of defaults
      $this->unpack_options($this->options, $options);
    }
  }

  /**
   * Return a string to display as the clickable title for the
   * access control.
   */
  function summary_title() {
    return t('Unknown');
  }

  /**
   * Determine the expiration time of the cache type, or NULL if no expire.
   *
   * Plugins must override this to implement expiration.
   *
   * @param $type
   *   The cache type, either 'query', 'result' or 'output'.
   */
   function cache_expire($type) { }

   /**
    * Determine expiration time in the cache table of the cache type
    * or CACHE_PERMANENT if item shouldn't be removed automatically from cache.
    *
    * Plugins must override this to implement expiration in the cache table.
    *
    * @param $type
    *   The cache type, either 'query', 'result' or 'output'.
    */
  function cache_set_expire($type) {
    return CACHE_PERMANENT;
  }


  /**
   * Save data to the cache.
   *
   * A plugin should override this to provide specialized caching behavior.
   */
  function cache_set($type) {
    switch ($type) {
      case 'query':
        // Not supported currently, but this is certainly where we'd put it.
        break;
      case 'results':
        $data = array(
          'result' => $this->view->result,
          'total_rows' => isset($this->view->total_rows) ? $this->view->total_rows : 0,
          'current_page' => $this->view->get_current_page(),
        );
        cache_set($this->get_results_key(), $data, $this->table, $this->cache_set_expire($type));
        break;
      case 'output':
        $this->gather_headers();
        $this->storage['output'] = $this->view->display_handler->output;
        cache_set($this->get_output_key(), $this->storage, $this->table, $this->cache_set_expire($type));
        break;
    }
  }


  /**
   * Retrieve data from the cache.
   *
   * A plugin should override this to provide specialized caching behavior.
   */
  function cache_get($type) {
    $cutoff = $this->cache_expire($type);
    switch ($type) {
      case 'query':
        // Not supported currently, but this is certainly where we'd put it.
        return FALSE;
      case 'results':
        // Values to set: $view->result, $view->total_rows, $view->execute_time,
        // $view->current_page.
        if ($cache = cache_get($this->get_results_key(), $this->table)) {
          if (!$cutoff || $cache->created > $cutoff) {
            $this->view->result = $cache->data['result'];
            $this->view->total_rows = $cache->data['total_rows'];
            $this->view->set_current_page($cache->data['current_page']);
            $this->view->execute_time = 0;
            return TRUE;
          }
        }
        return FALSE;
      case 'output':
        if ($cache = cache_get($this->get_output_key(), $this->table)) {
          if (!$cutoff || $cache->created > $cutoff) {
            $this->storage = $cache->data;
            $this->view->display_handler->output = $cache->data['output'];
            $this->restore_headers();
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
  function cache_flush() {
    cache_clear_all($this->view->name . ':', $this->table, TRUE);
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
  function post_render(&$output) { }

  /**
   * Start caching javascript, css and other out of band info.
   *
   * This takes a snapshot of the current system state so that we don't
   * duplicate it. Later on, when gather_headers() is run, this information
   * will be removed so that we don't hold onto it.
   */
  function cache_start() {
    $this->storage['head'] = drupal_add_html_head();
    $this->storage['css'] = drupal_add_css();
    $this->storage['js'] = drupal_add_js();
  }

  /**
   * Gather out of band data, compare it to what we started with and store the difference.
   */
  function gather_headers() {
    // Simple replacement for head
    if (isset($this->storage['head'])) {
      $this->storage['head'] = str_replace($this->storage['head'], '', drupal_add_html_head());
    }
    else {
      $this->storage['head'] = '';
    }

    // Slightly less simple for CSS:
    $css = drupal_add_css();
    $start = isset($this->storage['css']) ? $this->storage['css'] : array();
    $this->storage['css'] = array();

    foreach ($css as $file => $data) {
      if (!isset($this->storage['css'][$file])) {
        $this->storage['css'][$file] = $data;
      }
    }

    // Get javascript after views renders
    $js = drupal_add_js();

    // Get javascript before views renders.
    $start = isset($this->storage['js']) ? $this->storage['js'] : array();
    $this->storage['js'] = array();

    // If there are any differences between the old and the new javascript then
    // store them to be added later.
    if ($diff = array_diff_assoc($js, $start)) {
      $this->storage['js'] = $diff;
    }

    // Special case the settings key and get the difference of the data.
    if ($settings_diff = array_diff_assoc($js['settings']['data'], $start['settings']['data'])) {
      $this->storage['js']['settings'] = $settings_diff;
    }
  }

  /**
   * Restore out of band data saved to cache. Copied from Panels.
   */
  function restore_headers() {
    if (!empty($this->storage['head'])) {
      drupal_add_html_head($this->storage['head']);
    }
    if (!empty($this->storage['css'])) {
      foreach ($this->storage['css'] as $args) {
        drupal_add_css($args['data'], $args);
      }
    }
    if (!empty($this->storage['js'])) {
      foreach ($this->storage['js'] as $key => $args) {
        if ($key != 'settings') {
          drupal_add_js($args['data'], $args);
        }
        else {
          foreach ($args as $setting) {
            drupal_add_js($setting, 'setting');
          }
        }
      }
    }
  }

  function get_results_key() {
    global $user;

    if (!isset($this->_results_key)) {

      $build_info = $this->view->build_info;

      $query_plugin = $this->view->display_handler->get_plugin('query');

      foreach (array('query','count_query') as $index) {
        // If the default query back-end is used generate SQL query strings from
        // the query objects.
        if ($build_info[$index] instanceof SelectQueryInterface) {
          $query = clone $build_info[$index];
          $query->preExecute();
          $build_info[$index] = (string)$query;
        }
      }
      $key_data = array(
        'build_info' => $build_info,
        'roles' => array_keys($user->roles),
        'super-user' => $user->uid == 1, // special caching for super user.
        'language' => $GLOBALS['language']->language,
        'base_url' => $GLOBALS['base_url'],
      );
      foreach (array('exposed_info', 'page', 'sort', 'order', 'items_per_page', 'offset') as $key) {
        if (isset($_GET[$key])) {
          $key_data[$key] = $_GET[$key];
        }
      }

      $this->_results_key = $this->view->name . ':' . $this->display->id . ':results:' . md5(serialize($key_data));
    }

    return $this->_results_key;
  }

  function get_output_key() {
    global $user;
    if (!isset($this->_output_key)) {
      $key_data = array(
        'result' => $this->view->result,
        'roles' => array_keys($user->roles),
        'super-user' => $user->uid == 1, // special caching for super user.
        'theme' => $GLOBALS['theme'],
        'language' => $GLOBALS['language']->language,
        'base_url' => $GLOBALS['base_url'],
      );

      $this->_output_key = $this->view->name . ':' . $this->display->id . ':output:' . md5(serialize($key_data));
    }

    return $this->_output_key;
  }
}

/**
 * @}
 */

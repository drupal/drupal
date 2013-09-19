<?php

/**
 * @file
 * Contains Drupal\search\Controller\SearchController
 */

namespace Drupal\search\Controller;

/**
 * Route controller for search.
 */
class SearchController {

  /**
   * @todo Remove search_view().
   */
  public function searchView($keys) {
    module_load_include('pages.inc', 'search');
    return search_view(NULL, $keys);
  }

  /**
   * @todo Remove search_view().
   */
  public function searchViewPlugin($plugin_id, $keys) {
    module_load_include('pages.inc', 'search');
    return search_view($plugin_id, $keys);
  }

}

<?php

/**
 * @file
 * Functions to aid in presenting database results as a set of pages.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\UrlHelper;

/**
 * Returns the current page being requested for display within a pager.
 *
 * @param $element
 *  An optional integer to distinguish between multiple pagers on one page.
 *
 * @return
 *  The number of the current requested page, within the pager represented by
 *  $element. This is determined from the URL query parameter
 *  \Drupal::request()->query->get('page'), or 0 by default. Note that this
 *  number may differ from the actual page being displayed. For example, if a
 *  search for "example text" brings up three pages of results, but a users
 *  visits search/node/example+text?page=10, this function will return 10, even
 *  though the default pager implementation adjusts for this and still displays
 * the third page of search results at that URL.
 *
 * @see pager_default_initialize()
 */
function pager_find_page($element = 0) {
  $page = \Drupal::request()->query->get('page', '');
  $page_array = explode(',', $page);
  if (!isset($page_array[$element])) {
    $page_array[$element] = 0;
  }
  return (int) $page_array[$element];
}

/**
 * Initializes a pager for _theme('pager').
 *
 * This function sets up the necessary global variables so that future calls
 * to _theme('pager') will render a pager that correctly corresponds to the
 * items being displayed.
 *
 * If the items being displayed result from a database query performed using
 * Drupal's database API, and if you have control over the construction of the
 * database query, you do not need to call this function directly; instead, you
 * can simply extend the query object with the 'PagerSelectExtender' extender
 * before executing it. For example:
 * @code
 *   $query = db_select('some_table')
 *     ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
 * @endcode
 *
 * However, if you are using a different method for generating the items to be
 * paged through, then you should call this function in preparation.
 *
 * The following example shows how this function can be used in a page callback
 * that invokes an external datastore with an SQL-like syntax:
 * @code
 *   // First find the total number of items and initialize the pager.
 *   $where = "status = 1";
 *   $total = mymodule_select("SELECT COUNT(*) FROM data " . $where)->result();
 *   $num_per_page = \Drupal::config('mymodule.settings')->get('num_per_page');
 *   $page = pager_default_initialize($total, $num_per_page);
 *
 *   // Next, retrieve and display the items for the current page.
 *   $offset = $num_per_page * $page;
 *   $result = mymodule_select("SELECT * FROM data " . $where . " LIMIT %d, %d", $offset, $num_per_page)->fetchAll();
 *   $output = drupal_render(
 *     '#theme' => 'mymodule_results',
 *     '#result' => $result,
 *   );
 *
 *   // Finally, display the pager controls, and return.
 *   $pager = array('#theme' => 'pager');
 *   $output .= drupal_render($pager);
 *   return $output;
 * @endcode
 *
 * A second example involves a page callback that invokes an external search
 * service where the total number of matching results is provided as part of
 * the returned set (so that we do not need a separate query in order to obtain
 * this information). Here, we call pager_find_page() to calculate the desired
 * offset before the search is invoked:
 * @code
 *   // Perform the query, using the requested offset from pager_find_page().
 *   // This comes from a URL parameter, so here we are assuming that the URL
 *   // parameter corresponds to an actual page of results that will exist
 *   // within the set.
 *   $page = pager_find_page();
 *   $num_per_page = \Drupal::config('mymodule.settings')->get('num_per_page');
 *   $offset = $num_per_page * $page;
 *   $result = mymodule_remote_search($keywords, $offset, $num_per_page);
 *
 *   // Now that we have the total number of results, initialize the pager.
 *   pager_default_initialize($result->total, $num_per_page);
 *
 *   // Display the search results.
 *   $search_results = array(
 *     '#theme' => 'search_results',
 *     '#results' => $result->data,
 *     '#type' => 'remote',
 *   );
 *   $output = drupal_render($search_results);
 *
 *   // Finally, display the pager controls, and return.
 *   $pager = array('#theme' => 'pager');
 *   $output .= drupal_render($pager);
 *   return $output;
 * @endcode
 *
 * @param $total
 *  The total number of items to be paged.
 * @param $limit
 *  The number of items the calling code will display per page.
 * @param $element
 *  An optional integer to distinguish between multiple pagers on one page.
 *
 * @return
 *   The number of the current page, within the pager represented by $element.
 *   This is determined from the URL query parameter
 *   \Drupal::request()->query->get('page), or 0 by default. However, if a page
 *   that does not correspond to the actual range of the result set was
 *   requested, this function will return the closest page actually within the
 *   result set.
 */
function pager_default_initialize($total, $limit, $element = 0) {
  global $pager_page_array, $pager_total, $pager_total_items, $pager_limits;

  $page = pager_find_page($element);

  // We calculate the total of pages as ceil(items / limit).
  $pager_total_items[$element] = $total;
  $pager_total[$element] = ceil($pager_total_items[$element] / $limit);
  $pager_page_array[$element] = max(0, min($page, ((int) $pager_total[$element]) - 1));
  $pager_limits[$element] = $limit;
  return $pager_page_array[$element];
}

/**
 * Compose a URL query parameter array for pager links.
 *
 * @return
 *   A URL query parameter array that consists of all components of the current
 *   page request except for those pertaining to paging.
 */
function pager_get_query_parameters() {
  $query = &drupal_static(__FUNCTION__);
  if (!isset($query)) {
    $query = UrlHelper::filterQueryParameters(\Drupal::request()->query->all(), array('page'));
  }
  return $query;
}

/**
 * Prepares variables for pager templates.
 *
 * Default template: pager.html.twig.
 *
 * Menu callbacks that display paged query results should call _theme('pager')
 * to retrieve a pager control so that users can view other results. Format a
 * list of nearby pages with additional query results.
 *
 * @param array $variables
 *   An associative array containing:
 *   - tags: An array of labels for the controls in the pager.
 *   - element: An optional integer to distinguish between multiple pagers on
 *     one page.
 *   - parameters: An associative array of query string parameters to append to
 *     the pager links.
 *   - quantity: The number of pages in the list.
 */
function template_preprocess_pager(&$variables) {
  $element = $variables['element'];
  $parameters = $variables['parameters'];
  $quantity = $variables['quantity'];
  global $pager_page_array, $pager_total;

  // Nothing to do if there is only one page.
  if ($pager_total[$element] <= 1) {
    return;
  }

  $tags = $variables['tags'];

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil($quantity / 2);
  // current is the page we are currently paged to
  $pager_current = $pager_page_array[$element] + 1;
  // first is the first page listed by this pager piece (re quantity)
  $pager_first = $pager_current - $pager_middle + 1;
  // last is the last page listed by this pager piece (re quantity)
  $pager_last = $pager_current + $quantity - $pager_middle;
  // max is the maximum page number
  $pager_max = $pager_total[$element];
  // End of marker calculations.

  // Prepare for generation loop.
  $i = $pager_first;
  if ($pager_last > $pager_max) {
    // Adjust "center" if at end of query.
    $i = $i + ($pager_max - $pager_last);
    $pager_last = $pager_max;
  }
  if ($i <= 0) {
    // Adjust "center" if at start of query.
    $pager_last = $pager_last + (1 - $i);
    $i = 1;
  }
  // End of generation loop preparation.

  // Create the "first" and "previous" links if we are not on the first page.
  if ($pager_page_array[$element] > 0) {
    $items['first'] = array();
    $options = array(
      'query' => pager_query_add_page($parameters, $element, 0),
    );
    $items['first']['href'] = \Drupal::url('<current>', [], $options);
    if (isset($tags[0])) {
      $items['first']['text'] = $tags[0];
    }

    $items['previous'] = array();
    $options = array(
      'query' => pager_query_add_page($parameters, $element, $pager_page_array[$element] - 1),
    );
    $items['previous']['href'] = \Drupal::url('<current>', [], $options);
    if (isset($tags[1])) {
      $items['previous']['text'] = $tags[1];
    }
  }

  if ($i != $pager_max) {
    // Add an ellipsis if there are further previous pages.
    if ($i > 1) {
      $variables['ellipses']['previous'] = TRUE;
    }
    // Now generate the actual pager piece.
    for (; $i <= $pager_last && $i <= $pager_max; $i++) {
      $options = array(
        'query' => pager_query_add_page($parameters, $element, $i - 1),
      );
      $items['pages'][$i]['href'] = \Drupal::url('<current>', [], $options);
      if ($i == $pager_current) {
        $variables['current'] = $i;
      }
    }
    // Add an ellipsis if there are further next pages.
    if ($i < $pager_max) {
      $variables['ellipses']['next'] = TRUE;
    }
  }

  // Create the "next" and "last" links if we are not on the last page.
  if ($pager_page_array[$element] < ($pager_max - 1)) {
    $items['next'] = array();
    $options = array(
      'query' => pager_query_add_page($parameters, $element, $pager_page_array[$element] + 1),
    );
    $items['next']['href'] = \Drupal::url('<current>', [], $options);
    if (isset($tags[3])) {
      $items['next']['text'] = $tags[3];
    }

    $items['last'] = array();
    $options = array(
      'query' => pager_query_add_page($parameters, $element, $pager_max - 1),
    );
    $items['last']['href'] = \Drupal::url('<current>', [], $options);
    if (isset($tags[4])) {
      $items['last']['text'] = $tags[4];
    }
  }

  $variables['items'] = $items;
}

/**
 * Adds the 'page' parameter to the query parameter array of a pager link.
 *
 * @param array $query
 *   An associative array of query parameters to add to.
 * @param integer $element
 *   An integer to distinguish between multiple pagers on one page.
 * @param integer $index
 *   The index of the target page in the pager array.
 *
 * @return array
 *   The altered $query parameter array.
 *
 * @todo Document the pager/element/index architecture and logic. It is not
 *   clear what is happening in this function as well as pager_load_array(),
 *   and whether this can be simplified in any way.
 */
function pager_query_add_page(array $query, $element, $index) {
  global $pager_page_array;

  // Determine the first result to display on the linked page.
  $page_new = pager_load_array($index, $element, $pager_page_array);

  $page = \Drupal::request()->query->get('page', '');
  if ($new_page = implode(',', pager_load_array($page_new[$element], $element, explode(',', $page)))) {
    $query['page'] = $new_page;
  }
  // Merge the query parameters passed to this function with the parameters
  // from the current request. In case of collision, the parameters passed
  // into this function take precedence.
  if ($current_request_query = pager_get_query_parameters()) {
    $query = array_merge($current_request_query, $query);
  }
  return $query;
}

/**
 * Helper function
 *
 * Copies $old_array to $new_array and sets $new_array[$element] = $value
 * Fills in $new_array[0 .. $element - 1] = 0
 */
function pager_load_array($value, $element, $old_array) {
  $new_array = $old_array;
  // Look for empty elements.
  for ($i = 0; $i < $element; $i++) {
    if (empty($new_array[$i])) {
      // Load found empty element with 0.
      $new_array[$i] = 0;
    }
  }
  // Update the changed element.
  $new_array[$element] = (int) $value;
  return $new_array;
}

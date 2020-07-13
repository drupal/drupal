<?php

/**
 * @file
 * Functions to aid in presenting database results as a set of pages.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;

/**
 * Returns the current page being requested for display within a pager.
 *
 * @param int $element
 *   (optional) An integer to distinguish between multiple pagers on one page.
 *
 * @return int
 *   The number of the current requested page, within the pager represented by
 *   $element. This is determined from the URL query parameter
 *   \Drupal::request()->query->get('page'), or 0 by default. Note that this
 *   number may differ from the actual page being displayed. For example, if a
 *   search for "example text" brings up three pages of results, but a user
 *   visits search/node/example+text?page=10, this function will return 10,
 *   even though the default pager implementation adjusts for this and still
 *   displays the third page of search results at that URL.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Pager\PagerParametersInterface->findPage() instead.
 *
 * @see https://www.drupal.org/node/2779457
 * @see \Drupal\Core\Pager\PagerParametersInterface::findPage()
 */
function pager_find_page($element = 0) {
  @trigger_error(__FUNCTION__ . ' is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Pager\PagerParametersInterface->findPage() instead. See https://www.drupal.org/node/2779457', E_USER_DEPRECATED);
  /* @var $pager_parameters \Drupal\Core\Pager\PagerParametersInterface */
  $pager_parameters = \Drupal::service('pager.parameters');
  return $pager_parameters->findPage($element);
}

/**
 * Initializes a pager.
 *
 * This function sets up the necessary global variables so that the render
 * system will correctly process #type 'pager' render arrays to output pagers
 * that correspond to the items being displayed.
 *
 * If the items being displayed result from a database query performed using
 * Drupal's database API, and if you have control over the construction of the
 * database query, you do not need to call this function directly; instead, you
 * can simply extend the query object with the 'PagerSelectExtender' extender
 * before executing it. For example:
 * @code
 *   $query = \Drupal::database()->select('some_table')
 *     ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
 * @endcode
 *
 * However, if you are using a different method for generating the items to be
 * paged through, then you should call this function in preparation.
 *
 * The following example shows how this function can be used in a controller
 * that invokes an external datastore with an SQL-like syntax:
 * @code
 *   // First find the total number of items and initialize the pager.
 *   $where = "status = 1";
 *   $total = mymodule_select("SELECT COUNT(*) FROM data " . $where)->result();
 *   $num_per_page = \Drupal::config('mymodule.settings')->get('num_per_page');
 *   $page = pager_default_initialize($total, $num_per_page);
 *
 *   // Next, retrieve the items for the current page and put them into a
 *   // render array.
 *   $offset = $num_per_page * $page;
 *   $result = mymodule_select("SELECT * FROM data " . $where . " LIMIT %d, %d", $offset, $num_per_page)->fetchAll();
 *   $render = [];
 *   $render[] = [
 *     '#theme' => 'mymodule_results',
 *     '#result' => $result,
 *   ];
 *
 *   // Finally, add the pager to the render array, and return.
 *   $render[] = ['#type' => 'pager'];
 *   return $render;
 * @endcode
 *
 * A second example involves a controller that invokes an external search
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
 *   // Create a render array with the search results.
 *   $render = [];
 *   $render[] = [
 *     '#theme' => 'search_results',
 *     '#results' => $result->data,
 *     '#type' => 'remote',
 *   ];
 *
 *   // Finally, add the pager to the render array, and return.
 *   $render[] = ['#type' => 'pager'];
 *   return $render;
 * @endcode
 *
 * @param int $total
 *   The total number of items to be paged.
 * @param int $limit
 *   The number of items the calling code will display per page.
 * @param int $element
 *   (optional) An integer to distinguish between multiple pagers on one page.
 *
 * @return int
 *   The number of the current page, within the pager represented by $element.
 *   This is determined from the URL query parameter
 *   \Drupal::request()->query->get('page), or 0 by default. However, if a page
 *   that does not correspond to the actual range of the result set was
 *   requested, this function will return the closest page actually within the
 *   result set.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Pager\PagerManagerInterface->defaultInitialize() instead.
 *
 * @see https://www.drupal.org/node/2779457
 * @see \Drupal\Core\Pager\PagerManagerInterface::createPager()
 */
function pager_default_initialize($total, $limit, $element = 0) {
  @trigger_error(__FUNCTION__ . ' is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface->createPager() instead. See https://www.drupal.org/node/2779457', E_USER_DEPRECATED);
  /* @var $pager_manager \Drupal\Core\Pager\PagerManagerInterface */
  $pager_manager = \Drupal::service('pager.manager');
  $pager = $pager_manager->createPager($total, $limit, $element);
  return $pager->getCurrentPage();
}

/**
 * Compose a URL query parameter array for pager links.
 *
 * @return array
 *   A URL query parameter array that consists of all components of the current
 *   page request except for those pertaining to paging.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Pager\PagerParametersInterface->getQueryParameters() instead.
 *
 * @see https://www.drupal.org/node/2779457
 * @see \Drupal\Core\Pager\PagerParametersInterface::getQueryParameters()
 */
function pager_get_query_parameters() {
  @trigger_error(__FUNCTION__ . ' is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Pager\PagerParametersInterface->getQueryParameters() instead. See https://www.drupal.org/node/2779457', E_USER_DEPRECATED);
  /* @var $pager_params \Drupal\Core\Pager\PagerParametersInterface */
  $pager_params = \Drupal::service('pager.parameters');
  return $pager_params->getQueryParameters();
}

/**
 * Prepares variables for pager templates.
 *
 * Default template: pager.html.twig.
 *
 * Menu callbacks that display paged query results should use #type => pager
 * to retrieve a pager control so that users can view other results. Format a
 * list of nearby pages with additional query results.
 *
 * @param array $variables
 *   An associative array containing:
 *   - pager: A render element containing:
 *     - #tags: An array of labels for the controls in the pager.
 *     - #element: An optional integer to distinguish between multiple pagers on
 *       one page.
 *     - #parameters: An associative array of query string parameters to append
 *       to the pager links.
 *     - #route_parameters: An associative array of the route parameters.
 *     - #quantity: The number of pages in the list.
 */
function template_preprocess_pager(&$variables) {
  $element = $variables['pager']['#element'];
  $parameters = $variables['pager']['#parameters'];
  $quantity = empty($variables['pager']['#quantity']) ? 0 : $variables['pager']['#quantity'];
  $route_name = $variables['pager']['#route_name'];
  $route_parameters = isset($variables['pager']['#route_parameters']) ? $variables['pager']['#route_parameters'] : [];

  /* @var $pager_manager \Drupal\Core\Pager\PagerManagerInterface */
  $pager_manager = \Drupal::service('pager.manager');

  $pager = $pager_manager->getPager($element);

  // Nothing to do if there is no pager.
  if (!isset($pager)) {
    return;
  }

  $pager_max = $pager->getTotalPages();

  // Nothing to do if there is only one page.
  if ($pager_max <= 1) {
    return;
  }

  $tags = $variables['pager']['#tags'];

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil($quantity / 2);
  $current_page = $pager->getCurrentPage();
  // The current pager is the page we are currently paged to.
  $pager_current = $current_page + 1;
  // The first pager is the first page listed by this pager piece (re quantity).
  $pager_first = $pager_current - $pager_middle + 1;
  // The last is the last page listed by this pager piece (re quantity).
  $pager_last = $pager_current + $quantity - $pager_middle;
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
  if ($current_page > 0) {
    $items['first'] = [];
    $items['first']['attributes'] = new Attribute();
    $options = [
      'query' => $pager_manager->getUpdatedParameters($parameters, $element, 0),
    ];
    $items['first']['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
    if (isset($tags[0])) {
      $items['first']['text'] = $tags[0];
    }

    $items['previous'] = [];
    $items['previous']['attributes'] = new Attribute();
    $options = [
      'query' => $pager_manager->getUpdatedParameters($parameters, $element, $current_page - 1),
    ];
    $items['previous']['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
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
      $options = [
        'query' => $pager_manager->getUpdatedParameters($parameters, $element, $i - 1),
      ];
      $items['pages'][$i]['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
      $items['pages'][$i]['attributes'] = new Attribute();
      if ($i == $pager_current) {
        $variables['current'] = $i;
      }
    }
    // Add an ellipsis if there are further next pages.
    if ($i < $pager_max + 1) {
      $variables['ellipses']['next'] = TRUE;
    }
  }

  // Create the "next" and "last" links if we are not on the last page.
  if ($current_page < ($pager_max - 1)) {
    $items['next'] = [];
    $items['next']['attributes'] = new Attribute();
    $options = [
      'query' => $pager_manager->getUpdatedParameters($parameters, $element, $current_page + 1),
    ];
    $items['next']['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
    if (isset($tags[3])) {
      $items['next']['text'] = $tags[3];
    }

    $items['last'] = [];
    $items['last']['attributes'] = new Attribute();
    $options = [
      'query' => $pager_manager->getUpdatedParameters($parameters, $element, $pager_max - 1),
    ];
    $items['last']['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
    if (isset($tags[4])) {
      $items['last']['text'] = $tags[4];
    }
  }

  $variables['items'] = $items;
  $variables['heading_id'] = Html::getUniqueId('pagination-heading');

  // The rendered link needs to play well with any other query parameter used
  // on the page, like exposed filters, so for the cacheability all query
  // parameters matter.
  $variables['#cache']['contexts'][] = 'url.query_args';
}

/**
 * Gets the URL query parameter array of a pager link.
 *
 * Adds to or adjusts the 'page' URL query parameter so that if you follow the
 * link, you'll get page $index for pager $element on the page.
 *
 * The 'page' URL query parameter is a comma-delimited string, where each value
 * is the target content page for the corresponding pager $element. For
 * instance, if we have 5 pagers on a single page, and we want to have a link
 * to a page that should display the 6th content page for the 3rd pager, and
 * the 1st content page for all the other pagers, then the URL query will look
 * like this: ?page=0,0,5,0,0 (page numbering starts at zero).
 *
 * @param array $query
 *   An associative array of URL query parameters to add to.
 * @param int $element
 *   An integer to distinguish between multiple pagers on one page.
 * @param int $index
 *   The index of the target page, for the given element, in the pager array.
 *
 * @return array
 *   The altered $query parameter array.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Pager\PagerManagerInterface::getUpdatedParameters() instead.
 *
 * @see https://www.drupal.org/node/2779457
 * @see \Drupal\Core\Pager\PagerManagerInterface::getUpdatedParameters()
 */
function pager_query_add_page(array $query, $element, $index) {
  @trigger_error(__FUNCTION__ . ' is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface->getUpdatedParameters() instead. See https://www.drupal.org/node/2779457', E_USER_DEPRECATED);
  /* @var $pager_manager \Drupal\Core\Pager\PagerManagerInterface */
  $pager_manager = \Drupal::service('pager.manager');
  return $pager_manager->getUpdatedParameters($query, $element, $index);
}

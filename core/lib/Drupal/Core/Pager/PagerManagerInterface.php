<?php

namespace Drupal\Core\Pager;

/**
 * This is a service for pager information.
 *
 * The pager.manager service manages the pager information which will eventually
 * be rendered into pager elements in the response. To gather information
 * related to pager information in the request, use the pager.parameters
 * service.
 *
 * Since there can be multiple pagers per requested page, each one is
 * represented by an 'element' ID. This is an integer. It represents the index
 * of the pager element within the 'page' query. The value of the element is an
 * integer telling us the current page number for that pager.
 *
 * This class generally replaces the functions in core/includes/pager.inc. Those
 * functions use globals to store data which they all use. Since we require
 * backwards compatibility with this behavior, this class presents a public API
 * for using pager information, which is implemented using the same globals as a
 * 'backend.'
 *
 * @see \Drupal\Core\Pager\PagerParametersInterface
 */
interface PagerManagerInterface {

  /**
   * Initializes a pager.
   *
   * This function sets up the necessary variables so that the render system
   * will correctly process #type 'pager' render arrays to output pagers that
   * correspond to the items being displayed.
   *
   * If the items being displayed result from a database query performed using
   * Drupal's database API, and if you have control over the construction of the
   * database query, you do not need to call this function directly; instead,
   * you can extend the query object with the 'PagerSelectExtender' extender
   * before executing it. For example:
   * @code
   *   $query = db_select('some_table')
   *     ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
   * @endcode
   *
   * However, if you are using a different method for generating the items to be
   * paged through, then you should call this service in preparation.
   *
   * The following example shows how this service can be used in a controller
   * that invokes an external datastore with an SQL-like syntax:
   * @code
   *   // First find the total number of items and initialize the pager.
   *   $where = "status = 1";
   *   $total = mymodule_select("SELECT COUNT(*) FROM data " . $where)->result();
   *   $num_per_page = \Drupal::config('mymodule.settings')->get('num_per_page');
   *   $pager = \Drupal::service('pager.manager')->createPager($total, $num_per_page);
   *   $page = $pager->getCurrentPage();
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
   * the returned set (so that we do not need a separate query in order to
   * obtain this information). Here, we call PagerManagerInterface->findPage()
   * to calculate the desired offset before the search is invoked:
   * @code
   *
   *   // Perform the query, using the requested offset from
   *   // PagerManagerInterface::findPage(). This comes from a URL parameter, so
   *   // here we are assuming that the URL parameter corresponds to an actual
   *   // page of results that will exist within the set.
   *   $pager_parameters = \Drupal::service('pager.parameters');
   *   $page = $pager_parameters->findPage();
   *   $num_per_page = \Drupal::config('mymodule.settings')->get('num_per_page');
   *   $offset = $num_per_page * $page;
   *   $result = mymodule_remote_search($keywords, $offset, $num_per_page);
   *
   *   // Now that we have the total number of results, initialize the pager.
   *   $pager_manager = \Drupal::service('pager.manager');
   *   $pager_manager->createPager($result->total, $num_per_page);
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
   * @return \Drupal\Core\Pager\Pager
   *   The pager.
   */
  public function createPager($total, $limit, $element = 0);

  /**
   * Gets a pager from the static cache.
   *
   * @param int $element
   *   The pager element index.
   *
   * @return \Drupal\Core\Pager\Pager|null
   *   The pager, or null if not found.
   */
  public function getPager($element = 0);

  /**
   * Gets the URL query parameter array of a pager link.
   *
   * Adds to or adjusts the 'page' URL query parameter so that if you follow the
   * link, you'll get page $index for pager $element on the page.
   *
   * The 'page' URL query parameter is a comma-delimited string, where each
   * value is the target content page for the corresponding pager $element. For
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
   */
  public function getUpdatedParameters(array $query, $element, $index);

}

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
   *   $query = $connection->select('some_table')
   *     ->extend(PagerSelectExtender::class);
   * @endcode
   *
   * However, if you are using a different method for generating the items to be
   * paged through, then you should call this service in preparation.
   *
   * The following example shows how this service can be used in a controller
   * that invokes an external datastore with an SQL-like syntax:
   * @code
   *   // First find the total number of items and initialize the pager.
   *   $total = my_module_select("SELECT COUNT(*) FROM data WHERE status = 1")->result();
   *   $num_per_page = \Drupal::config('my_module.settings')->get('num_per_page');
   *   $pager = \Drupal::service('pager.manager')->createPager($total, $num_per_page);
   *   $page = $pager->getCurrentPage();
   *
   *   // Next, retrieve the items for the current page and put them into a
   *   // render array.
   *   $offset = $num_per_page * $page;
   *   $result = my_module_select("SELECT * FROM data " . $where . " LIMIT %d, %d", $offset, $num_per_page)->fetchAll();
   *   $render = [];
   *   $render[] = [
   *     '#theme' => 'my_module_results',
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
   *   $pager_manager = \Drupal::service('pager.manager');
   *   $page = $pager_manager->findPage();
   *   $num_per_page = \Drupal::config('my_module.settings')->get('num_per_page');
   *   $offset = $num_per_page * $page;
   *   $result = my_module_remote_search($keywords, $offset, $num_per_page);
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
   * Returns the current page being requested for display within a pager.
   *
   * @param int $pager_id
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
   */
  public function findPage(int $pager_id = 0): int;

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

  /**
   * Gets the extent of the pager page element IDs.
   *
   * @return int
   *   The maximum element ID available, -1 if there are no elements.
   */
  public function getMaxPagerElementId();

  /**
   * Reserve a pager element ID.
   *
   * Calling code may need to reserve the ID of a pager before actually creating
   * it. This methods allows to do so ensuring no collision occurs with
   * ::getMaxPagerElementId().
   *
   * @param int $element
   *   The ID of the pager to be reserved.
   *
   * @see \Drupal\Core\Database\Query\PagerSelectExtender::element()
   */
  public function reservePagerElementId(int $element): void;

}

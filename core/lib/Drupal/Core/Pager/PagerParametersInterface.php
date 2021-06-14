<?php

namespace Drupal\Core\Pager;

/**
 * Interface describing pager information contained within the request.
 *
 * @see \Drupal\Core\Pager\PagerManagerInterface
 */
interface PagerParametersInterface {

  /**
   * Gets all request URL query parameters that are unrelated to paging.
   *
   * @return array
   *   A URL query parameter array that consists of all components of the
   *   current page request except for those pertaining to paging.
   */
  public function getQueryParameters();

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
  public function findPage($pager_id = 0);

  /**
   * Gets the request query parameter.
   *
   * @return int[]
   *   Array of pagers. Keys are integers which are the element ID. Values are
   *   the zero-based current page from the request. The first page is 0, the
   *   second page is 1, etc.
   */
  public function getPagerQuery();

  /**
   * Gets the 'page' query parameter for the current request.
   *
   * @return string
   *   The 'page' query parameter for the current request. This is a
   *   comma-delimited string of pager element values. Defaults to empty string
   *   if the query does not have a 'page' parameter.
   */
  public function getPagerParameter();

}

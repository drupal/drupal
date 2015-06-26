<?php

/**
 * @file
 * Contains \Drupal\search\SearchPageRepositoryInterface.
 */

namespace Drupal\search;

/**
 * Provides the interface for a repository Search Page entities.
 */
interface SearchPageRepositoryInterface {

  /**
   * Returns all active search page entities.
   *
   * @return \Drupal\search\SearchPageInterface[]
   *   An array of active search page entities.
   */
  public function getActiveSearchPages();

  /**
   * Returns whether search is active.
   *
   * @return bool
   *   TRUE if at least one search is active, FALSE otherwise.
   */
  public function isSearchActive();

  /**
   * Returns all active, indexable search page entities.
   *
   * @return \Drupal\search\SearchPageInterface[]
   *   An array of indexable search page entities.
   */
  public function getIndexableSearchPages();

  /**
   * Returns the default search page.
   *
   * @return \Drupal\search\SearchPageInterface|bool
   *   The search page entity, or FALSE if no pages are active.
   */
  public function getDefaultSearchPage();

  /**
   * Sets a given search page as the default.
   *
   * @param \Drupal\search\SearchPageInterface $search_page
   *   The search page entity.
   *
   * @return static
   */
  public function setDefaultSearchPage(SearchPageInterface $search_page);

  /**
   * Clears the default search page.
   */
  public function clearDefaultSearchPage();

  /**
   * Sorts a list of search pages.
   *
   * @param \Drupal\search\SearchPageInterface[] $search_pages
   *   The unsorted list of search pages.
   *
   * @return \Drupal\search\SearchPageInterface[]
   *   The sorted list of search pages.
   */
  public function sortSearchPages($search_pages);

}

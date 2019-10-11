<?php

namespace Drupal\Core\Pager;

/**
 * A value object that represents a pager.
 */
class Pager {

  /**
   * The total number of items .
   *
   * @var int
   */
  protected $totalItems;

  /**
   * The total number of pages.
   *
   * @var int
   */
  protected $totalPages;

  /**
   * The current page of the pager.
   *
   * @var int
   */
  protected $currentPage;

  /**
   * The maximum number of items per page.
   *
   * @var int
   */
  protected $limit;

  /**
   * Pager constructor.
   *
   * @param int $totalItems
   *   The total number of items.
   * @param int $limit
   *   The maximum number of items per page.
   * @param int $currentPage
   *   The current page.
   */
  public function __construct($totalItems, $limit, $currentPage = 0) {
    $this->totalItems = $totalItems;
    $this->limit = $limit;
    $this->setTotalPages($totalItems, $limit);
    $this->setCurrentPage($currentPage);
  }

  /**
   * Sets the current page to a valid value within range.
   *
   * If a page that does not correspond to the actual range of the result set
   * was provided, this function will set the closest page actually within
   * the result set.
   *
   * @param int $currentPage
   *   (optional) The current page.
   */
  protected function setCurrentPage($currentPage = 0) {
    $this->currentPage = max(0, min($currentPage, $this->getTotalPages() - 1));
  }

  /**
   * Sets the total number of pages.
   *
   * @param int $totalItems
   *   The total number of items.
   * @param int $limit
   *   The maximum number of items per page.
   */
  protected function setTotalPages($totalItems, $limit) {
    $this->totalPages = (int) ceil($totalItems / $limit);
  }

  /**
   * Gets the total number of items.
   *
   * @return int
   *   The total number of items.
   */
  public function getTotalItems() {
    return $this->totalItems;
  }

  /**
   * Gets the total number of pages.
   *
   * @return int
   *   The total number of pages.
   */
  public function getTotalPages() {
    return $this->totalPages;
  }

  /**
   * Gets the current page.
   *
   * @return int
   *   The current page.
   */
  public function getCurrentPage() {
    return $this->currentPage;
  }

  /**
   * Gets the maximum number of items per page.
   *
   * @return int
   *   The the maximum number of items per page.
   */
  public function getLimit() {
    return $this->limit;
  }

}

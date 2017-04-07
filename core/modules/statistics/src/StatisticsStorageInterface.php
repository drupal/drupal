<?php

namespace Drupal\statistics;

/**
 * Provides an interface defining Statistics Storage.
 *
 * Stores the views per day, total views and timestamp of last view
 * for entities.
 */
interface StatisticsStorageInterface {

  /**
   * Count a entity view.
   *
   * @param int $id
   *   The ID of the entity to count.
   *
   * @return bool
   *   TRUE if the entity view has been counted.
   */
  public function recordView($id);

  /**
   * Returns the number of times entities have been viewed.
   *
   * @param array $ids
   *   An array of IDs of entities to fetch the views for.
   *
   * @return \Drupal\statistics\StatisticsViewsResult[]
   *   An array of value objects representing the number of times each entity
   *   has been viewed. The array is keyed by entity ID. If an ID does not
   *   exist, it will not be present in the array.
   */
  public function fetchViews($ids);

  /**
   * Returns the number of times a single entity has been viewed.
   *
   * @param int $id
   *   The ID of the entity to fetch the views for.
   *
   * @return \Drupal\statistics\StatisticsViewsResult|false
   *   If the entity exists, a value object representing the number of times if
   *   has been viewed. If it does not exist, FALSE is returned.
   */
  public function fetchView($id);

  /**
   * Returns the number of times a entity has been viewed.
   *
   * @param string $order
   *   The counter name to order by:
   *   - 'totalcount' The total number of views.
   *   - 'daycount' The number of views today.
   *   - 'timestamp' The unix timestamp of the last view.
   *
   * @param int $limit
   *   The number of entity IDs to return.
   *
   * @return array
   *   An ordered array of entity IDs.
   */
  public function fetchAll($order = 'totalcount', $limit = 5);

  /**
   * Delete counts for a specific entity.
   *
   * @param int $id
   *   The ID of the entity which views to delete.
   *
   * @return bool
   *   TRUE if the entity views have been deleted.
   */
  public function deleteViews($id);

  /**
   * Reset the day counter for all entities once every day.
   */
  public function resetDayCount();

  /**
   * Returns the highest 'totalcount' value.
   *
   * @return int
   *   The highest 'totalcount' value.
   */
  public function maxTotalCount();

}

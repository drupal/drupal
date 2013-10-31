<?php

/**
 * @file
 * Contains \Drupal\aggregator\CategoryStorageControllerInterface.
 */

namespace Drupal\aggregator;

/**
 * Storage Controller for aggregator categories.
 */
interface CategoryStorageControllerInterface {

  /**
   * Loads an aggregator category by its unique ID.
   *
   * @param int $cid
   *   The unique category ID.
   *
   * @return stdClass|null
   *   An object containing all category properties.
   */
  public function load($cid);

  /**
   * Saves an aggregator category.
   *
   * @param \stdClass $category
   *   The category to save.
   *
   * @return int
   *   The new category ID.
   */
  public function save($category);

  /**
   * Updates and aggregator category.
   *
   * @param \stdClass $category
   *   The category.
   */
  public function update($category);

  /**
   * Deletes an aggregator category.
   *
   * @param int $cid
   *   The category ID.
   */
  public function delete($cid);

  /**
   * Checks if the category title is unique.
   *
   * Optionally passes a category ID to exclude, if this check is for an
   * existing category.
   *
   * @param string $title
   *   The category title.
   * @param int $cid
   *   (optional) The category ID to exclude from the check.
   *
   * @return bool
   *   TRUE if the category title is unique, FALSE otherwise.
   */
  public function isUnique($title, $cid = NULL);

  /**
   * Loads aggregator categories for an aggregator item.
   *
   * @param int $item_id
   *   The aggregator item ID.
   *
   * @return array
   *   An array of objects containing item ID, category ID and title.
   */
  public function loadByItem($item_id);

  /**
   * Updates the categories for an aggregator item.
   *
   * @param int $iid
   *   The aggregator item ID.
   * @param array $cids
   *   The category IDs.
   */
  public function updateItem($iid, array $cids);

  /**
   * Loads all categories.
   *
   * @return array
   *   An array keyed on cid listing all available categories.
   */
  public function loadAllKeyed();

}


<?php

/**
 * @file
 * Contains Drupal\aggregator\ItemStorageControllerInterface.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\Entity\Item;
use Drupal\core\Entity\EntityStorageControllerInterface;

/**
 * Defines a common interface for aggregator item entity controller classes.
 */
interface ItemStorageControllerInterface extends EntityStorageControllerInterface {

  /**
   * Load the categories for aggregator items.
   *
   * @param array $entities
   *   An array of aggregator item objects, keyed by the item id. Each object
   *   will get a categories property added.
   */
  public function loadCategories(array $entities);

  /**
   * Delete the categories for aggregator items.
   *
   * @param array $entities
   *   An array of aggregator item objects, keyed by the item id being
   *   deleted. The storage backend should delete the category data of the
   *   items.
   */
  public function deleteCategories(array $entities);

  /**
   * Store the categories for aggregator items.
   *
   * @param Item $item
   *   The storage backend should save the categories of this item.
   */
  public function saveCategories(Item $item);

  /**
   * Loads feed items from all feeds.
   *
   * @param int $limit
   *   (optional) The number of items to return. Defaults to 20.
   *
   * @return \Drupal\aggregator\ItemInterface[]
   *   An array of the feed items.
   */
  public function loadAll($limit = 20);

  /**
   * Loads feed items filtered by a feed.
   *
   * @param int $fid
   *   The feed ID to filter by.
   * @param int $limit
   *   (optional) The number of items to return. Defaults to 20.
   *
   * @return \Drupal\aggregator\ItemInterface[]
   *   An array of the feed items.
   */
  public function loadByFeed($fid, $limit = 20);

  /**
   * Loads feed items from all feeds.
   *
   * @param int $cid
   *   The category ID to filter by.
   * @param int $limit
   *   (optional) The number of items to return. Defaults to 20.
   *
   * @return \Drupal\aggregator\ItemInterface[]
   *   An array of the feed items.
   */
  public function loadByCategory($cid, $limit = 20);

}

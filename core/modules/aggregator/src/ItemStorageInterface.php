<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemStorageInterface.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an interface for aggregator item entity storage classes.
 */
interface ItemStorageInterface extends EntityStorageInterface {

  /**
   * Returns the count of the items in a feed.
   *
   * @param \Drupal\aggregator\FeedInterface $feed
   *   The feed entity.
   *
   * @return int
   *   The count of items associated with a feed.
   */
  public function getItemCount(FeedInterface $feed);

  /**
   * Loads feed items from all feeds.
   *
   * @param int $limit
   *   (optional) The number of items to return. Defaults to unlimited.
   *
   * @return \Drupal\aggregator\ItemInterface[]
   *   An array of the feed items.
   */
  public function loadAll($limit = NULL);

  /**
   * Loads feed items filtered by a feed.
   *
   * @param int $fid
   *   The feed ID to filter by.
   * @param int $limit
   *   (optional) The number of items to return. Defaults to unlimited.
   *
   * @return \Drupal\aggregator\ItemInterface[]
   *   An array of the feed items.
   */
  public function loadByFeed($fid, $limit = NULL);

}

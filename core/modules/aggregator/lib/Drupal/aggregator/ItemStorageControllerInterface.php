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

}

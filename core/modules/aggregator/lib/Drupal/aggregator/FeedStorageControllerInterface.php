<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageControllerInterface.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\Entity\Feed;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a common interface for aggregator feed entity controller classes.
 */
interface FeedStorageControllerInterface extends EntityStorageControllerInterface {

  /**
   * Loads the categories of a feed.
   *
   * @param array $entities
   *   A list of feed entities keyed by feed id. Each entity will get a
   *   categories property added.
   */
  public function loadCategories(array $feeds);

  /**
   * Saves the categories of a feed.
   *
   * @param Feed $feed
   *   The feed entity.
   * @param array $categories
   *   The array of categories.
   */
  public function saveCategories(Feed $feed, array $categories);

  /**
   * Deletes the categories of a feed.
   *
   * @param array $feeds
   *   A list of feed entities keyed by feed id.
   */
  public function deleteCategories(array $feeds);

}

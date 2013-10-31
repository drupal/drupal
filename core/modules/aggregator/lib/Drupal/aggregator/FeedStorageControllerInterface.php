<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageControllerInterface.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a common interface for aggregator feed entity controller classes.
 */
interface FeedStorageControllerInterface extends EntityStorageControllerInterface {

  /**
   * Loads the categories of a feed.
   *
   * @param array $feeds
   *   A list of feed entities keyed by feed id. Each entity will get a
   *   categories property added.
   */
  public function loadCategories(array $feeds);

  /**
   * Saves the categories of a feed.
   *
   * @param \Drupal\aggregator\Entity\FeedInterface $feed
   *   The feed entity.
   * @param array $categories
   *   The array of categories.
   */
  public function saveCategories(FeedInterface $feed, array $categories);

  /**
   * Deletes the categories of a feed.
   *
   * @param array $feeds
   *   A list of feed entities keyed by feed id.
   */
  public function deleteCategories(array $feeds);

  /**
   * Provides a list of duplicate feeds.
   *
   * @param \Drupal\aggregator\Entity\FeedInterface $feed
   *   The feed entity.
   *
   * @return
   *   An array with the list of duplicated feeds.
   */
  public function getFeedDuplicates(FeedInterface $feed);

}

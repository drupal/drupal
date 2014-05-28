<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageInterface.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a common interface for aggregator feed entity controller classes.
 */
interface FeedStorageInterface extends EntityStorageInterface {

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

  /**
   * Returns the fids of feeds that need to be refreshed.
   *
   *  @return array
   *    A list of feed ids to be refreshed.
   */
  public function getFeedIdsToRefresh();

}

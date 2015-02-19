<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageInterface.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an interface for aggregator feed entity storage classes.
 */
interface FeedStorageInterface extends EntityStorageInterface {

  /**
   * Returns the fids of feeds that need to be refreshed.
   *
   * @return array
   *   A list of feed ids to be refreshed.
   */
  public function getFeedIdsToRefresh();

}

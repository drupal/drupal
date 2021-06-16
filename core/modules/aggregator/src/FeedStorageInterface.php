<?php

namespace Drupal\aggregator;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for aggregator feed entity storage classes.
 */
interface FeedStorageInterface extends ContentEntityStorageInterface {

  /**
   * Denotes that a feed's items should never expire.
   */
  const CLEAR_NEVER = 0;

  /**
   * Returns the fids of feeds that need to be refreshed.
   *
   * @return array
   *   A list of feed ids to be refreshed.
   */
  public function getFeedIdsToRefresh();

}

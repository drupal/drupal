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

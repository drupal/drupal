<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Core\Entity\FeedInterface.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an aggregator feed entity.
 */
interface FeedInterface extends ContentEntityInterface {

  /**
   * Removes all items from a feed.
   */
  public function removeItems();

}

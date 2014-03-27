<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemsImporterInterface.
 */

namespace Drupal\aggregator;

/**
 * Provides an interface defining an aggregator items importer.
 */
interface ItemsImporterInterface {

  /**
   * Updates the feed items by triggering the import process.
   *
   * This process can be slow and lengthy because it relies on network
   * operations. Calling it on performance critical paths should be avoided.
   *
   * @param \Drupal\aggregator\FeedInterface $feed
   *   The feed which items should be refreshed.
   *
   * @return bool
   *   TRUE if there is new content for the feed FALSE otherwise.
   */
  public function refresh(FeedInterface $feed);

  /**
   * Deletes all imported items from a feed.
   *
   * @param \Drupal\aggregator\FeedInterface $feed
   *   The feed that associated items should be deleted from.
   */
  public function delete(FeedInterface $feed);

}

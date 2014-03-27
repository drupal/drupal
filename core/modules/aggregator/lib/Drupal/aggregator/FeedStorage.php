<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorage.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;

/**
 * Controller class for aggregator's feeds.
 *
 * This extends the Drupal\Core\Entity\ContentEntityDatabaseStorage class,
 * adding required special handling for feed entities.
 */
class FeedStorage extends ContentEntityDatabaseStorage implements FeedStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getFeedDuplicates(FeedInterface $feed) {
    $query = \Drupal::entityQuery('aggregator_feed');

    $or_condition = $query->orConditionGroup()
      ->condition('title', $feed->label())
      ->condition('url', $feed->getUrl());
    $query->condition($or_condition);

    if ($feed->id()) {
      $query->condition('fid', $feed->id(), '<>');
    }

    return $this->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedIdsToRefresh() {
    return $this->database->query('SELECT fid FROM {aggregator_feed} WHERE queued = 0 AND checked + refresh < :time AND refresh <> :never', array(
      ':time' => REQUEST_TIME,
      ':never' => AGGREGATOR_CLEAR_NEVER
    ))->fetchCol();
  }

}

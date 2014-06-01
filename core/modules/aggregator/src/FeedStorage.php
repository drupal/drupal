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
  public function getSchema() {
    $schema = parent::getSchema();

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['aggregator_feed']['fields']['url']['not null'] = TRUE;
    $schema['aggregator_feed']['fields']['queued']['not null'] = TRUE;
    $schema['aggregator_feed']['fields']['title']['not null'] = TRUE;

    $schema['aggregator_feed']['indexes'] += array(
      'aggregator_feed__url'  => array(array('url', 255)),
      'aggregator_feed__queued' => array('queued'),
    );
    $schema['aggregator_feed']['unique keys'] += array(
      'aggregator_feed__title' => array('title'),
    );

    return $schema;
  }

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

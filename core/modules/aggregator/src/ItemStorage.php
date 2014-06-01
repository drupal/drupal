<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemStorage.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\Entity\Item;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;

/**
 * Controller class for aggregators items.
 *
 * This extends the Drupal\Core\Entity\ContentEntityDatabaseStorage class,
 * adding required special handling for feed item entities.
 */
class ItemStorage extends ContentEntityDatabaseStorage implements ItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['aggregator_item']['fields']['timestamp']['not null'] = TRUE;

    $schema['aggregator_item']['indexes'] += array(
      'aggregator_item__timestamp' => array('timestamp'),
    );
    $schema['aggregator_item']['foreign keys'] += array(
      'aggregator_item__aggregator_feed' => array(
        'table' => 'aggregator_feed',
        'columns' => array('fid' => 'fid'),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemCount(FeedInterface $feed) {
    $query = \Drupal::entityQuery('aggregator_item')
      ->condition('fid', $feed->id())
      ->count();

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll($limit = NULL) {
    $query = \Drupal::entityQuery('aggregator_item');
    return $this->executeFeedItemQuery($query, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByFeed($fid, $limit = NULL) {
    $query = \Drupal::entityQuery('aggregator_item')
      ->condition('fid', $fid);
    return $this->executeFeedItemQuery($query, $limit);
  }

  /**
   * Helper method to execute an item query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to execute.
   * @param int $limit
   *   (optional) The number of items to return.
   *
   * @return \Drupal\aggregator\ItemInterface[]
   *   An array of the feed items.
   */
  protected function executeFeedItemQuery(QueryInterface $query, $limit) {
    $query->sort('timestamp', 'DESC')
      ->sort('iid', 'DESC');
    if (!empty($limit)) {
      $query->pager($limit);
    }

    return $this->loadMultiple($query->execute());
  }

}

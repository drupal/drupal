<?php

namespace Drupal\aggregator;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for feed item entities.
 *
 * This extends the base storage class, adding required special handling for
 * feed item entities.
 */
class ItemStorage extends SqlContentEntityStorage implements ItemStorageInterface {

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
    $query->accessCheck(FALSE)
      ->sort('timestamp', 'DESC')
      ->sort('iid', 'DESC');
    if (!empty($limit)) {
      $query->pager($limit);
    }

    return $this->loadMultiple($query->execute());
  }

}

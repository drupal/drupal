<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemStorageController.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\Entity\Item;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\FieldableDatabaseStorageController;

/**
 * Controller class for aggregators items.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for feed item entities.
 */
class ItemStorageController extends FieldableDatabaseStorageController implements ItemStorageControllerInterface {

  /**
   * {@inheritdoc}
   */
  public function loadAll($limit = 20) {
    $query = $this->database->select('aggregator_item', 'i');
    $query->join('aggregator_feed', 'f', 'i.fid = f.fid');
    $query->fields('i', array('iid'));
    return $this->executeFeedItemQuery($query, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByFeed($fid, $limit = 20) {
    $query = $this->database->select('aggregator_item', 'i');
    $query
      ->fields('i', array('iid'))
      ->condition('i.fid', $fid);
    return $this->executeFeedItemQuery($query, $limit);
  }

  /**
   * Helper method to execute an item query.
   *
   * @param SelectInterface $query
   *   The query to execute.
   * @param int $limit
   *   (optional) The number of items to return. Defaults to 20.
   *
   * @return \Drupal\aggregator\ItemInterface[]
   *   An array of the feed items.
   */
  protected function executeFeedItemQuery(SelectInterface $query, $limit) {
    $result = $query
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit($limit)
      ->orderBy('i.timestamp', 'DESC')
      ->orderBy('i.iid', 'DESC')
      ->execute();

    return $this->loadMultiple($result->fetchCol());
  }

}

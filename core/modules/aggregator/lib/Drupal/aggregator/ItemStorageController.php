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
   * Overrides Drupal\Core\Entity\DataBaseStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    parent::attachLoad($queried_entities, $load_revision);
    $this->loadCategories($queried_entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadCategories(array $entities) {
    foreach ($entities as $item) {
      $item->categories = db_query('SELECT c.title, c.cid FROM {aggregator_category_item} ci LEFT JOIN {aggregator_category} c ON ci.cid = c.cid WHERE ci.iid = :iid ORDER BY c.title', array(':iid' => $item->id()))->fetchAll();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCategories(array $entities) {
    $this->database->delete('aggregator_category_item')
      ->condition('iid', array_keys($entities))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function saveCategories(Item $item) {
    $result = $this->database->query('SELECT cid FROM {aggregator_category_feed} WHERE fid = :fid', array(':fid' => $item->getFeedId()));
    foreach ($result as $category) {
      $this->database->merge('aggregator_category_item')
        ->key(array(
          'iid' => $item->id(),
          'cid' => $category->cid,
        ))
        ->execute();
    }
  }

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
   * {@inheritdoc}
   */
  public function loadByCategory($cid, $limit = 20) {
    $query = $this->database->select('aggregator_category_item', 'c');
    $query->leftJoin('aggregator_item', 'i', 'c.iid = i.iid');
    $query->leftJoin('aggregator_feed', 'f', 'i.fid = f.fid');
    $query
      ->fields('i', array('iid'))
      ->condition('cid', $cid);
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

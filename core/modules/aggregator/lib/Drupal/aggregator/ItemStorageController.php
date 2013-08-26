<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemStorageController.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\Entity\Item;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * Controller class for aggregators items.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for feed item entities.
 */
class ItemStorageController extends DatabaseStorageControllerNG implements ItemStorageControllerInterface {

  /**
   * Overrides Drupal\Core\Entity\DataBaseStorageController::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    parent::attachLoad($queried_entities, $load_revision);
    $this->loadCategories($queried_entities);
  }

  /**
   * Implements Drupal\Core\Entity\DataBaseStorageControllerNG::baseFieldDefinitions().
   */
  public function baseFieldDefinitions() {
    $fields['iid'] = array(
      'label' => t('ID'),
      'description' => t('The ID of the aggregor item.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['fid'] = array(
      'label' => t('Aggregator feed ID'),
      'description' => t('The ID of the aggregator feed.'),
      'type' => 'integer_field',
    );
    $fields['title'] = array(
      'label' => t('Title'),
      'description' => t('The title of the feed item.'),
      'type' => 'string_field',
    );
    $fields['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The feed item language code.'),
      'type' => 'language_field',
    );
    $fields['link'] = array(
      'label' => t('Link'),
      'description' => t('The link of the feed item.'),
      'type' => 'uri_field',
    );
    $fields['author'] = array(
      'label' => t('Author'),
      'description' => t('The author of the feed item.'),
      'type' => 'string_field',
    );
    $fields['description'] = array(
      'label' => t('Description'),
      'description' => t('The body of the feed item.'),
      'type' => 'string_field',
    );
    $fields['timestamp'] = array(
      'label' => t('Posted timestamp'),
      'description' => t('Posted date of the feed item, as a Unix timestamp.'),
      'type' => 'integer_field',
    );
    $fields['guid'] = array(
      'label' => t('GUID'),
      'description' => t('Unique identifier for the feed item.'),
      'type' => 'string_field',
    );
    return $fields;
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
    $result = $this->database->query('SELECT cid FROM {aggregator_category_feed} WHERE fid = :fid', array(':fid' => $item->fid->value));
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

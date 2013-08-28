<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageController.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\aggregator\Entity\Feed;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for aggregators feeds.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for feed entities.
 */
class FeedStorageController extends DatabaseStorageControllerNG implements FeedStorageControllerInterface {

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
  public function loadCategories(array $feeds) {
    foreach ($feeds as $feed) {
      $feed->categories = $this->database->query('SELECT c.cid, c.title FROM {aggregator_category} c JOIN {aggregator_category_feed} f ON c.cid = f.cid AND f.fid = :fid ORDER BY title', array(':fid' => $feed->id()))->fetchAllKeyed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveCategories(Feed $feed, array $categories) {
    foreach ($categories as $cid => $value) {
      if ($value) {
        $this->database->insert('aggregator_category_feed')
          ->fields(array(
            'fid' => $feed->id(),
            'cid' => $cid,
          ))
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCategories(array $feeds) {
    // An existing feed is being modified, delete the category listings.
    $this->database->delete('aggregator_category_feed')
      ->condition('fid', array_keys($feeds))
      ->execute();
  }

}

<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageController.
 */

namespace Drupal\aggregator;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Entity\FieldableDatabaseStorageController;

/**
 * Controller class for aggregator's feeds.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for feed entities.
 */
class FeedStorageController extends FieldableDatabaseStorageController implements FeedStorageControllerInterface {

  /**
   * {@inheritdoc}
   */
  public function getFeedDuplicates(FeedInterface $feed) {
    if ($feed->id()) {
      $query = $this->database->query("SELECT title, url FROM {aggregator_feed} WHERE (title = :title OR url = :url) AND fid <> :fid", array(':title' => $feed->label(), ':url' => $feed->url->value, ':fid' => $feed->id()));
    }
    else {
      $query = $this->database->query("SELECT title, url FROM {aggregator_feed} WHERE title = :title OR url = :url", array(':title' => $feed->label(), ':url' => $feed->url->value));
    }

    return $query->fetchAll();
  }

}

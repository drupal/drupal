<?php

namespace Drupal\aggregator;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for aggregator's feeds.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class, adding
 * required special handling for feed entities.
 */
class FeedStorage extends SqlContentEntityStorage implements FeedStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getFeedIdsToRefresh() {
    return $this->database->query('SELECT fid FROM {' . $this->getBaseTable() . '} WHERE queued = 0 AND checked + refresh < :time AND refresh <> :never', [
      ':time' => REQUEST_TIME,
      ':never' => static::CLEAR_NEVER,
    ])->fetchCol();
  }

}

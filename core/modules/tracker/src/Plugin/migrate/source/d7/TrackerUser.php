<?php

namespace Drupal\tracker\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 tracker user source from database.
 *
 * @MigrateSource(
 *   id = "d7_tracker_user",
 *   source_provider = "tracker"
 * )
 */
class TrackerUser extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('tracker_user', 'tu')->fields('tu');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('The {user}.nid this record tracks.'),
      'uid' => $this->t('The {users}.uid of the node author or commenter.'),
      'published' => $this->t('Boolean indicating whether the node is published.'),
      'changed' => $this->t('The Unix timestamp when the user was most recently saved or commented on.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['uid']['type'] = 'integer';
    return $ids;
  }

}

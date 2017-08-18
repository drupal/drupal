<?php

namespace Drupal\user\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 user picture source from database.
 *
 * @todo Support default picture?
 *
 * @MigrateSource(
 *   id = "d6_user_picture",
 *   source_module = "user"
 * )
 */
class UserPicture extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('users', 'u')
      ->condition('picture', '', '<>')
      ->fields('u', ['uid', 'access', 'picture'])
      ->orderBy('u.access');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uid' => 'Primary Key: Unique user ID.',
      'access' => 'Timestamp for previous time user accessed the site.',
      'picture' => "Path to the user's uploaded picture.",
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['uid']['type'] = 'integer';
    return $ids;
  }

}

<?php
/**
 * @file
 * Contains \Drupal\shortcut\Plugin\migrate\source\d7\ShortcutSetUsers.
 */

namespace Drupal\shortcut\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 shortcut_set_users source from database.
 *
 * @MigrateSource(
 *   id = "d7_shortcut_set_users",
 *   source_provider = "shortcut"
 * )
 */
class ShortcutSetUsers extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('shortcut_set_users', 'ssu')->fields('ssu');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'uid' => $this->t('The users.uid for this set.'),
      'set_name' => $this->t('The shortcut_set.set_name that will be displayed for this user.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'set_name' => array(
        'type' => 'string',
      ),
      'uid' => array(
        'type' => 'integer',
      ),
    );
  }

}

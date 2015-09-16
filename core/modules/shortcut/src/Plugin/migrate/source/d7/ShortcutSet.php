<?php
/**
 * @file
 * Contains \Drupal\shortcut\Plugin\migrate\source\d7\ShortcutSet.
 */

namespace Drupal\shortcut\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 shortcut_set source from database.
 *
 * @MigrateSource(
 *   id = "d7_shortcut_set",
 *   source_provider = "shortcut"
 * )
 */
class ShortcutSet extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('shortcut_set', 'ss')->fields('ss');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'set_name' => $this->t("The name under which the set's links are stored."),
      'title' => $this->t("The title of the set."),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['set_name']['type'] = 'string';
    return $ids;
  }

}

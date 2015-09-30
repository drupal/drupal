<?php
/**
 * @file
 * Contains \Drupal\shortcut\Plugin\migrate\source\d7\Shortcut.
 */

namespace Drupal\shortcut\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 shortcut links source from database.
 *
 * @MigrateSource(
 *   id = "d7_shortcut",
 *   source_provider = "shortcut"
 * )
 */
class Shortcut extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('menu_links', 'ml')
      ->fields('ml', array('mlid', 'menu_name', 'link_path', 'link_title', 'weight'))
      ->condition('hidden', '0')
      ->condition('menu_name', 'shortcut-set-%', 'LIKE');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'mlid' => $this->t("The menu.mlid primary key for this menu item (= shortcut link)."),
      'menu_name' => $this->t("The menu_name (= set name) for this shortcut link."),
      'link_path' => $this->t("The link for this shortcut."),
      'link_title' => $this->t("The title for this shortcut."),
      'weight' => $this->t("The weight for this shortcut"),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['mlid']['type'] = 'integer';
    return $ids;
  }

}

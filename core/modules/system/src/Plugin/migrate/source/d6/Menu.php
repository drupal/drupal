<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\migrate\source\d6\Menu.
 */

namespace Drupal\system\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 menu source from database.
 *
 * @MigrateSource(
 *   id = "d6_menu",
 *   source_provider = "menu"
 * )
 */
class Menu extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('menu_custom', 'm')
      ->fields('m', array('menu_name', 'title', 'description'));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'menu_name' => $this->t('The menu name. Primary key.'),
      'title' => $this->t('The human-readable name of the menu.'),
      'description' => $this->t('A description of the menu'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['menu_name']['type'] = 'string';
    return $ids;
  }

}

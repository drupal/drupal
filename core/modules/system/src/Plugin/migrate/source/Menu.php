<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\migrate\source\Menu.
 */

namespace Drupal\system\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Menu source from database.
 *
 * @MigrateSource(
 *   id = "menu",
 *   source_provider = "menu"
 * )
 */
class Menu extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('menu_custom', 'm')->fields('m');
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

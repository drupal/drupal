<?php

namespace Drupal\system\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6/7 menu source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "menu",
 *   source_module = "menu"
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
    $fields = [
      'menu_name' => $this->t('The menu name. Primary key.'),
      'title' => $this->t('The human-readable name of the menu.'),
      'description' => $this->t('A description of the menu'),
    ];

    // The database connection may not exist, for example, when building
    // the Migrate Message form.
    if ($source_database = $this->database) {
      if ($source_database
        ->schema()
        ->fieldExists('menu_custom', 'language')) {
        $fields += [
          'language' => $this->t('Menu language.'),
          'i8n_mode' => $this->t('Menu i18n mode.'),
        ];
      }
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['menu_name']['type'] = 'string';
    return $ids;
  }

}

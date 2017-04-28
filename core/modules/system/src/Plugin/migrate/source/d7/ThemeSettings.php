<?php

namespace Drupal\system\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\VariableMultiRow;

/**
 * Drupal 7 system source from database.
 *
 * @MigrateSource(
 *   id = "d7_theme_settings",
 *   source_provider = "system"
 * )
 */
class ThemeSettings extends VariableMultiRow {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('name', 'theme_%_settings', 'LIKE');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Theme settings variable for a theme.'),
      'value' => $this->t('The theme settings variable value.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

}

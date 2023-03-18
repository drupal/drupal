<?php

namespace Drupal\update\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * Drupal 6/7 Update settings source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate_drupal\Plugin\migrate\source\Variable
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "update_settings",
 *   source_module = "update"
 * )
 */
class UpdateSettings extends Variable {

  /**
   * {@inheritdoc}
   */
  protected function values() {
    $values = parent::values();
    if (empty($values['update_fetch_url']) || str_contains($values['update_fetch_url'], 'http://updates.drupal.org/release-history')) {
      $values['update_fetch_url'] = 'https://updates.drupal.org/release-history';
    }
    return $values;
  }

}

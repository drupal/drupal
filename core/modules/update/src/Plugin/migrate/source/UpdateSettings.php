<?php

namespace Drupal\update\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * Update settings source plugin.
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
    if (empty($values['update_fetch_url']) || strpos($values['update_fetch_url'], 'http://updates.drupal.org/release-history') !== FALSE) {
      $values['update_fetch_url'] = 'https://updates.drupal.org/release-history';
    }
    return $values;
  }

}

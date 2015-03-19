<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\MigratePluginManager.
 */

namespace Drupal\migrate_drupal\Plugin;

use Drupal\migrate\Plugin\MigratePluginManager as BaseMigratePluginManager;

/**
 * Manages migrate_drupal plugins.
 *
 * @see plugin_api
 *
 * @ingroup migration
 */
class MigratePluginManager extends BaseMigratePluginManager {

  /**
   * {@inheritdoc}
   */
  protected function getPluginInterfaceMap() {
    return parent::getPluginInterfaceMap() + [
      'load' => 'Drupal\migrate_drupal\Plugin\MigrateLoadInterface',
      'cckfield' => 'Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface',
    ];
  }

}

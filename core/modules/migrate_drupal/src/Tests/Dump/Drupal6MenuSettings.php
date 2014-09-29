<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6MenuSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing menu_ui.settings.yml migration.
 */
class Drupal6MenuSettings extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('variable');
    $this->setModuleVersion('menu', 6000);
    $this->database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'menu_override_parent_selector',
      'value' => 'b:0;',
    ))
    ->execute();
  }
}

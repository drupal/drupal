<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6ContactSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing contact.settings.yml migration.
 */
class Drupal6ContactSettings extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('variable');
    $this->database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'contact_default_status',
      'value' => 'i:1;',
    ))
    ->values(array(
      'name' => 'contact_hourly_threshold',
      'value' => 'i:3;',
    ))
    ->execute();
  }
}

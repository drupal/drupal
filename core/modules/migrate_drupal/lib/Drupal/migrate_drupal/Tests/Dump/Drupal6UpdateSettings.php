<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6UpdateSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing update.settings.yml migration.
 */
class Drupal6UpdateSettings extends Drupal6DumpBase {

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
      'name' => 'update_max_fetch_attempts',
      'value' => 'i:2;',
    ))
    ->values(array(
      'name' => 'update_fetch_url',
      'value' => 's:41:"http://updates.drupal.org/release-history";',
    ))
    ->values(array(
      'name' => 'update_notification_threshold',
      'value' => 's:3:"all";',
    ))
    ->values(array(
      'name' => 'update_notify_emails',
      'value' => 'a:0:{}',
    ))
    ->execute();
  }
}

<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SyslogSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing syslog.settings.yml migration.
 */
class Drupal6SyslogSettings extends Drupal6DumpBase {

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
      'name' => 'syslog_facility',
      'value' => 'i:128;',
    ))
    ->values(array(
      'name' => 'syslog_identity',
      'value' => 's:6:"drupal";',
    ))
    ->execute();
  }
}

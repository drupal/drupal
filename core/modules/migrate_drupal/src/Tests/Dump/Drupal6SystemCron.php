<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemCron.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing system.cron.yml migration.
 */
class Drupal6SystemCron extends Drupal6DumpBase {

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
      'name' => 'cron_threshold_warning',
      'value' => 'i:172800;',
    ))
    ->values(array(
      'name' => 'cron_threshold_error',
      'value' => 'i:1209600;',
    ))
    ->execute();
  }
}

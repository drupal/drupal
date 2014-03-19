<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6StatisticsSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing statistics.settings.yml migration.
 */
class Drupal6StatisticsSettings extends Drupal6DumpBase {

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
      'name' => 'statistics_enable_access_log',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'statistics_flush_accesslog_timer',
      'value' => 'i:259200;',
    ))
    ->values(array(
      'name' => 'statistics_count_content_views',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'statistics_block_top_day_num',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'statistics_block_top_all_num',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'statistics_block_top_last_num',
      'value' => 'i:0;',
    ))
    ->execute();
  }
}

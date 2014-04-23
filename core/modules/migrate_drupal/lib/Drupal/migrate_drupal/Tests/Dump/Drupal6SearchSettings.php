<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SearchSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing forum.site.yml migration.
 */
class Drupal6SearchSettings extends Drupal6DumpBase {

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
      'name' => 'minimum_word_size',
      'value' => 's:1:"3";',
    ))
    ->values(array(
      'name' => 'overlap_cjk',
      'value' => 'i:1;',
    ))
    ->values(array(
      'name' => 'search_cron_limit',
      'value' => 's:3:"100";',
    ))
    ->execute();
  }
}

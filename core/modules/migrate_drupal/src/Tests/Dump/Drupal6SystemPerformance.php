<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemPerformance.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing system.performance.yml migration.
 */
class Drupal6SystemPerformance extends Drupal6DumpBase {

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
      'name' => 'preprocess_css',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'preprocess_js',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'cache_lifetime',
      'value' => 'i:0;',
    ))
    ->values(array(
      'name' => 'cache',
      'value' => 'i:1;',
    ))
    ->execute();
  }

}

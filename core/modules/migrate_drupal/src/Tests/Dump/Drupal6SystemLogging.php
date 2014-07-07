<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemLogging.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing system.logging.yml migration.
 */
class Drupal6SystemLogging extends Drupal6DumpBase {

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
      'name' => 'error_level',
      'value' => serialize(1),
    ))
    ->execute();
  }

}

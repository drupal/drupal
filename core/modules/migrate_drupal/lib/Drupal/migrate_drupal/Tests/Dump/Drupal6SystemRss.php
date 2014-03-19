<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemRss.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing system.settings.yml migration.
 */
class Drupal6SystemRss extends Drupal6DumpBase {

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
      'name' => 'feed_default_items',
      'value' => 'i:10;',
    ))
    ->execute();
  }
}

<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemImageGd.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing system.image.gd.yml migration.
 */
class Drupal6SystemImageGd extends Drupal6DumpBase {

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
      'name' => 'image_jpeg_quality',
      'value' => 'i:75;',
    ))
    ->execute();
  }

}

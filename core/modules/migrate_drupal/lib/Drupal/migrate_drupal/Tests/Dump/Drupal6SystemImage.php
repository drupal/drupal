<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemImage.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing system.image.yml migration.
 */
class Drupal6SystemImage extends Drupal6DumpBase {

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
      'name' => 'image_toolkit',
      'value' => 's:2:"gd";',
    ))
    ->execute();
  }

}

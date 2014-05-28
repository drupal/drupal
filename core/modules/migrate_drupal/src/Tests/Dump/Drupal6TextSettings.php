<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6TextSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing text.settings.yml migration.
 */
class Drupal6TextSettings extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('variable');
    // This needs to be a merge to avoid conflicts with Drupal6NodeBodyInstance.
    $this->database->merge('variable')
      ->key(array('name' => 'teaser_length'))
      ->fields(array('value' => 'i:456;'))
      ->execute();
  }
}

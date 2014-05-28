<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6FieldSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing field.settings.yml migration.
 */
class Drupal6FieldSettings extends Drupal6DumpBase {

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
      'name' => 'field_language_fallback',
      'value' => 'b:1;',
    ))
    ->execute();
  }
}

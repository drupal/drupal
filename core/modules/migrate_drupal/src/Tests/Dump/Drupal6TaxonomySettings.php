<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6TaxonomySettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing taxonomy.settings.yml migration.
 */
class Drupal6TaxonomySettings extends Drupal6DumpBase {

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
      'name' => 'taxonomy_override_selector',
      'value' => 'b:0;',
    ))
    ->values(array(
      'name' => 'taxonomy_terms_per_page_admin',
      'value' => 'i:100;',
    ))
    ->execute();
  }
}

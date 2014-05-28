<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6LocaleSettings.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing locale.settings.yml migration.
 */
class Drupal6LocaleSettings extends Drupal6DumpBase {

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
      'name' => 'locale_cache_strings',
      'value' => 'i:1;',
    ))
    ->values(array(
      'name' => 'locale_js_directory',
      'value' => 's:9:"languages";',
    ))
    ->execute();
  }

}

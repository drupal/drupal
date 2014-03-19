<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6SystemFile.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing system.file.yml migration.
 */
class Drupal6SystemFile extends Drupal6DumpBase {

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
      'name' => 'file_directory_path',
      // This sets up MigrateDrupal6Test to pass. Do not change.
      'value' => 's:29:"core/modules/simpletest/files";',
    ))
    ->values(array(
      'name' => 'file_directory_temp',
      'value' => 's:10:"files/temp";',
    ))
    ->execute();
  }

  /**
   * Dump for the standalone test in MigrateFileTest.
   */
  public function loadMigrateFileStandalone() {
    $this->createTable('variable');
    $this->database->insert('variable')->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'file_directory_path',
      'value' => 's:10:"files/test";',
    ))
    ->execute();
  }

}

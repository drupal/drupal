<?php

namespace Drupal\Tests\file\Kernel\Migrate\d6;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Helper for setting up a file migration test.
 */
trait FileMigrationTestTrait {

  /**
   * Setup and execute d6_file migration.
   */
  protected function setUpMigratedFiles() {
    $this->installEntitySchema('file');
    $this->installConfig(['file']);

    $this->executeMigration('d6_file');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMigration(MigrationInterface $migration) {
    // File migrations need a source_base_path.
    // @see MigrateUpgradeRunBatch::run
    $destination = $migration->getDestinationConfiguration();
    if ($destination['plugin'] === 'entity:file') {
      // Make sure we have a single trailing slash.
      $source = $migration->getSourceConfiguration();
      $source['site_path'] = 'core/modules/simpletest';
      $source['constants']['source_base_path'] = \Drupal::root() . '/';
      $migration->set('source', $source);
    }
  }

}

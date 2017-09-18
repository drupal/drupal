<?php

namespace Drupal\Tests\file\Kernel\Migrate\d7;


use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * A trait to setup the file migration.
 */
trait FileMigrationSetupTrait {

  /**
   * Prepare the file migration for running.
   */
  protected function fileMigrationSetup() {
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->container->get('stream_wrapper_manager')->registerWrapper('public', PublicStream::class, StreamWrapperInterface::NORMAL);

    $fs = \Drupal::service('file_system');
    // The public file directory active during the test will serve as the
    // root of the fictional Drupal 7 site we're migrating.
    $fs->mkdir('public://sites/default/files', NULL, TRUE);
    file_put_contents('public://sites/default/files/cube.jpeg', str_repeat('*', 3620));

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d7_file');
    // Set the source plugin's source_base_path configuration value, which
    // would normally be set by the user running the migration.
    $source = $migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs->realpath('public://');
    $migration->set('source', $source);
    $this->executeMigration($migration);
  }

}

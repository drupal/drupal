<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Migrate\d7;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * A trait to setup the file migration.
 */
trait FileMigrationSetupTrait {

  /**
   * Returns information about the file to be migrated.
   *
   * @return array
   *   Array with keys 'path', 'size', 'base_path', and 'plugin_id'.
   */
  abstract protected function getFileMigrationInfo();

  /**
   * Prepare the file migration for running.
   */
  protected function fileMigrationSetup() {
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    $info = $this->getFileMigrationInfo();
    $fs = $this->container->get('file_system');
    // Ensure that the files directory exists.
    $fs->mkdir(dirname($info['path']), NULL, TRUE);
    // Put test file in the source directory.
    file_put_contents($info['path'], str_repeat('*', $info['size']));

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration($info['plugin_id']);
    // Set the source plugin's source_base_path configuration value, which
    // would normally be set by the user running the migration.
    $source = $migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs->realpath($info['base_path']);
    $migration->set('source', $source);
    $this->executeMigration($migration);
  }

  /**
   * Tests a single file entity.
   *
   * @param int $id
   *   The file ID.
   * @param string $name
   *   The expected file name.
   * @param string $uri
   *   The expected URI.
   * @param string $mime
   *   The expected MIME type.
   * @param string $size
   *   The expected file size.
   * @param string $created
   *   The expected creation time.
   * @param string $changed
   *   The expected modification time.
   * @param string $uid
   *   The expected owner ID.
   */
  protected function assertEntity($id, $name, $uri, $mime, $size, $created, $changed, $uid) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($id);
    $this->assertInstanceOf(FileInterface::class, $file);
    $this->assertSame($name, $file->getFilename());
    $this->assertSame($uri, $file->getFileUri());
    $this->assertFileExists($uri);
    $this->assertSame($mime, $file->getMimeType());
    $this->assertSame($size, $file->getSize());
    // isPermanent(), isTemporary(), etc. are determined by the status column.
    $this->assertTrue($file->isPermanent());
    $this->assertSame($created, $file->getCreatedTime());
    $this->assertSame($changed, $file->getChangedTime());
    $this->assertSame($uid, $file->getOwnerId());
  }

}

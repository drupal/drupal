<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFileTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\Core\Database\Database;

/**
 * file migration.
 *
 * @group migrate_drupal
 */
class MigrateFileTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Set the temp file of the site to the same as the D6 site, this allows us
    // to test files which start and finish in the same place.
    $this->tempFilesDirectory = '/tmp';
    parent::setUp();
    $dumps = array(
      $this->getDumpDirectory() . '/Files.php',
    );
    /** @var \Drupal\migrate\Entity\MigrationInterface $migration */
    $migration = entity_load('migration', 'd6_file');
    $source = $migration->get('source');
    $source['conf_path'] = 'core/modules/simpletest';
    $migration->set('source', $source);
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
    $this->standalone = TRUE;
    file_put_contents('/tmp/some-temp-file.jpg', '');
  }

  /**
   * Tests the Drupal 6 files to Drupal 8 migration.
   */
  public function testFiles() {
    /** @var \Drupal\file\FileInterface $file */
    $file = entity_load('file', 1);
    $this->assertIdentical($file->getFilename(), 'Image1.png');
    $this->assertIdentical($file->getSize(), '39325');
    $this->assertIdentical($file->getFileUri(), 'public://image-1.png');
    $this->assertIdentical($file->getMimeType(), 'image/png');
    // It is pointless to run the second half from MigrateDrupal6Test.
    if (empty($this->standalone)) {
      return;
    }

    // Test that we can re-import and also test with file_directory_path set.
    db_truncate(entity_load('migration', 'd6_file')->getIdMap()->mapTableName())->execute();
    $migration = entity_load_unchanged('migration', 'd6_file');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);

    // Update the file_directory_path.
    Database::getConnection('default', 'migrate')
      ->update('variable')
      ->fields(array('value' => serialize('files/test')))
      ->condition('name', 'file_directory_path')
      ->execute();
    Database::getConnection('default', 'migrate')
      ->update('variable')
      ->fields(array('value' => serialize('/tmp')))
      ->condition('name', 'file_directory_temp')
      ->execute();
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $file = entity_load('file', 2);
    $this->assertIdentical($file->getFileUri(), 'public://core/modules/simpletest/files/image-2.jpg');

    // Ensure that a temporary file has been migrated.
    $file = entity_load('file', 6);
    $this->assertIdentical($file->getFileUri(), 'temporary://some-temp-file.jpg');
  }

}

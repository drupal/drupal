<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFileTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Component\Utility\Random;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Tests\MigrateDumpAlterInterface;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\Core\Database\Database;
use Drupal\simpletest\TestBase;

/**
 * file migration.
 *
 * @group migrate_drupal
 */
class MigrateFileTest extends MigrateDrupal6TestBase implements MigrateDumpAlterInterface {

  /**
   * The filename of a file used to test temporary file migration.
   *
   * @var string
   */
  protected static $tempFilename;

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
  }

  /**
   * Tests the Drupal 6 files to Drupal 8 migration.
   */
  public function testFiles() {
    /** @var \Drupal\file\FileInterface $file */
    $file = entity_load('file', 1);
    $this->assertIdentical('Image1.png', $file->getFilename());
    $this->assertIdentical('39325', $file->getSize());
    $this->assertIdentical('public://image-1.png', $file->getFileUri());
    $this->assertIdentical('image/png', $file->getMimeType());
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
      ->fields(array('value' => serialize($this->getTempFilesDirectory())))
      ->condition('name', 'file_directory_temp')
      ->execute();
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $file = entity_load('file', 2);
    $this->assertIdentical('public://core/modules/simpletest/files/image-2.jpg', $file->getFileUri());

    // Ensure that a temporary file has been migrated.
    $file = entity_load('file', 6);
    $this->assertIdentical('temporary://' . static::getUniqueFilename(), $file->getFileUri());
  }

  /**
   * @return string
   *   A filename based upon the test.
   */
  public static function getUniqueFilename() {
    return static::$tempFilename;
  }

  /**
   * {@inheritdoc}
   */
  public static function migrateDumpAlter(TestBase $test) {
    // Creates a random filename and updates the source database.
    $random = new Random();
    $temp_directory = $test->getTempFilesDirectory();
    static::$tempFilename = $test->getDatabasePrefix() . $random->name() . '.jpg';
    $file_path = $temp_directory . '/' . static::$tempFilename;
    file_put_contents($file_path, '');
    Database::getConnection('default', 'migrate')
      ->update('files')
      ->condition('fid', 6)
      ->fields(array(
        'filename' => static::$tempFilename,
        'filepath' => $file_path,
      ))
      ->execute();

    return static::$tempFilename;
  }

}

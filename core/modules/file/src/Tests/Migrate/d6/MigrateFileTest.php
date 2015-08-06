<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Migrate\d6\MigrateFileTest.
 */

namespace Drupal\file\Tests\Migrate\d6;

use Drupal\Component\Utility\Random;
use Drupal\migrate\Tests\MigrateDumpAlterInterface;
use Drupal\Core\Database\Database;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\simpletest\TestBase;
use Drupal\file\Entity\File;

/**
 * file migration.
 *
 * @group file
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

    $this->installEntitySchema('file');
    $this->installConfig(['file']);

    $this->loadDumps(['Files.php']);
    /** @var \Drupal\migrate\Entity\MigrationInterface $migration */
    $migration = entity_load('migration', 'd6_file');
    $source = $migration->get('source');
    $source['site_path'] = 'core/modules/simpletest';
    $migration->set('source', $source);
    $this->executeMigration($migration);
    $this->standalone = TRUE;
  }

  /**
   * Tests the Drupal 6 files to Drupal 8 migration.
   */
  public function testFiles() {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load(1);
    $this->assertIdentical('Image1.png', $file->getFilename());
    $this->assertIdentical('39325', $file->getSize());
    $this->assertIdentical('public://image-1.png', $file->getFileUri());
    $this->assertIdentical('image/png', $file->getMimeType());
    $this->assertIdentical("1", $file->getOwnerId());

    // It is pointless to run the second half from MigrateDrupal6Test.
    if (empty($this->standalone)) {
      return;
    }

    // Test that we can re-import and also test with file_directory_path set.
    db_truncate(entity_load('migration', 'd6_file')->getIdMap()->mapTableName())->execute();

    $this->loadDumps(['Variable.php']);

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

    $migration = entity_load_unchanged('migration', 'd6_file');
    $this->executeMigration($migration);

    $file = File::load(2);
    $this->assertIdentical('public://core/modules/simpletest/files/image-2.jpg', $file->getFileUri());

    // Ensure that a temporary file has been migrated.
    $file = File::load(6);
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
    file_prepare_directory($temp_directory, FILE_CREATE_DIRECTORY);
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

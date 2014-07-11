<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateFileTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * file migration.
 *
 * @group migrate_drupal
 */
class MigrateFileTest extends MigrateDrupalTestBase {

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
      $this->getDumpDirectory() . '/Drupal6File.php',
    );
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_file');
    $migration->source['conf_path'] = 'core/modules/simpletest';
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
    $this->assertEqual($file->getFilename(), 'Image1.png');
    $this->assertEqual($file->getSize(), 39325);
    $this->assertEqual($file->getFileUri(), 'public://image-1.png');
    $this->assertEqual($file->getMimeType(), 'image/png');
    // It is pointless to run the second half from MigrateDrupal6Test.
    if (empty($this->standalone)) {
      return;
    }

    // Test that we can re-import and also test with file_directory_path set.
    db_truncate(entity_load('migration', 'd6_file')->getIdMap()->mapTableName())->execute();
    $migration = entity_load_unchanged('migration', 'd6_file');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SystemFile.php',
    );
    $this->loadDumps($dumps, 'loadMigrateFileStandalone');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $file = entity_load('file', 2);
    $this->assertEqual($file->getFileUri(), 'public://core/modules/simpletest/files/image-2.jpg');
  }

}

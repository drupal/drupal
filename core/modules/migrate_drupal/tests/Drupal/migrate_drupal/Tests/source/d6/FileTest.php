<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\source\d6\FileTest.
 */

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\migrate\Tests\MigrateSqlSourceTestCase;

/**
 * Tests the Drupal 6 file source.
 *
 * @group migrate_drupal
 * @group Drupal
 */
class FileTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\d6\File';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    // Leave it empty for now.
    'idlist' => array(),
    'source' => array(
      'plugin' => 'd6_file',
    ),
  );

  protected $expectedResults = array(
    array(
      'fid' => 1,
      'uid' => 1,
      'filename' => 'migrate-test-file-1.pdf',
      'filepath' => 'sites/default/files/migrate-test-file-1.pdf',
      'filemime' => 'application/pdf',
      'filesize' => 890404,
      'status' => 1,
      'timestamp' => 1382255613,
    ),
    array(
      'fid' => 2,
      'uid' => 1,
      'filename' => 'migrate-test-file-2.pdf',
      'filepath' => 'sites/default/files/migrate-test-file-2.pdf',
      'filemime' => 'application/pdf',
      'filesize' => 204124,
      'status' => 1,
      'timestamp' => 1382255662,
    ),
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'D6 file source functionality',
      'description' => 'Tests D6 file source plugin.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['files'] = $this->expectedResults;
    parent::setUp();
  }

}

namespace Drupal\migrate_drupal\Tests\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\File;

class TestFile extends File {
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }
}

<?php

/**
 * @file
 * Contains \Drupal\Tests\file\Unit\Plugin\migrate\source\d6\FileTest.
 */

namespace Drupal\Tests\file\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 file source plugin.
 *
 * @group file
 */
class FileTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\file\Plugin\migrate\source\d6\File';

  protected $migrationConfiguration = array(
    'id' => 'test',
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
  protected function setUp() {
    $this->databaseContents['files'] = $this->expectedResults;
    parent::setUp();
  }

}

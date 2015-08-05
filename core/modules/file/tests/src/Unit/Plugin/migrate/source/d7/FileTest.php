<?php

/**
 * @file
 * Contains \Drupal\Tests\file\Unit\Plugin\migrate\source\d7\FileTest.
 */

namespace Drupal\Tests\file\Unit\Plugin\migrate\source\d7;

use Drupal\file\Plugin\migrate\source\d7\File;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 file source plugin.
 *
 * @group file
 */
class FileTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\Tests\file\Unit\Plugin\migrate\source\d7\TestFile';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'test',
    'source' => array(
      'plugin' => 'd7_file',
    ),
  );

  protected $expectedResults = [
    [
      'fid' => '1',
      'uid' => '1',
      'filename' => 'cube.jpeg',
      'uri' => 'public://cube.jpeg',
      'filemime' => 'image/jpeg',
      'filesize' => '3620',
      'status' => '1',
      'timestamp' => '1421727515',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['file_managed'] = $this->expectedResults;
    parent::setUp();
  }

  /**
   * Tests that public file URIs are properly transformed by prepareRow().
   */
  public function testPublicUri() {
    $this->source->publicPath = 'sites/default/files';
    $row = new Row(['uri' => 'public://foo.png'], ['uri' => []]);
    $this->source->prepareRow($row);
    $this->assertEquals('sites/default/files/foo.png',
      $row->getSourceProperty('filepath'));
  }

  /**
   * Tests that private file URIs are properly transformed by prepareRow().
   */
  public function testPrivateUri() {
    $this->source->privatePath = '/path/to/private/files';
    $row = new Row(['uri' => 'private://baz.jpeg'], ['uri' => []]);
    $this->source->prepareRow($row);
    $this->assertEquals('/path/to/private/files/baz.jpeg',
      $row->getSourceProperty('filepath'));
  }

  /**
   * Tests that temporary file URIs are property transformed by prepareRow().
   */
  public function testTemporaryUri() {
    $this->source->temporaryPath = '/tmp';
    $row = new Row(['uri' => 'temporary://camelot/lancelot.gif'],
      ['uri' => []]);
    $this->source->prepareRow($row);
    $this->assertEquals('/tmp/camelot/lancelot.gif',
      $row->getSourceProperty('filepath'));
  }

}

/**
 * Testing version of \Drupal\file\Plugin\migrate\source\d7\File.
 *
 * Exposes inaccessible properties.
 */
class TestFile extends File {

  /**
   * The public file directory path.
   *
   * @var string
   */
  public $publicPath;

  /**
   * The private file directory path, if any.
   *
   * @var string
   */
  public $privatePath;

  /**
   * The temporary file directory path.
   *
   * @var string
   */
  public $temporaryPath;

}

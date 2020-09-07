<?php

namespace Drupal\Tests\file\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 file source plugin.
 *
 * @covers \Drupal\file\Plugin\migrate\source\d7\File
 * @group file
 */
class FileTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    $tests[0]['source_data']['file_managed'] = [
      // A public file.
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
      // A private file.
      [
        'fid' => '1',
        'uid' => '1',
        'filename' => 'cube.jpeg',
        'uri' => 'private://cube.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => '3620',
        'status' => '1',
        'timestamp' => '1421727515',
      ],
      // A temporary file.
      [
        'fid' => '1',
        'uid' => '1',
        'filename' => 'cube.jpeg',
        'uri' => 'temporary://cube.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => '3620',
        'status' => '1',
        'timestamp' => '1421727515',
      ],
      // A file with a URI scheme that will be filtered out.
      [
        'fid' => '1',
        'uid' => '1',
        'filename' => 'cube.jpeg',
        'uri' => 'null://cube.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => '3620',
        'status' => '1',
        'timestamp' => '1421727515',
      ],
    ];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'file_public_path',
        'value' => serialize('sites/default/files'),
      ],
      [
        'name' => 'file_private_path',
        'value' => serialize('/path/to/private/files'),
      ],
      [
        'name' => 'file_temporary_path',
        'value' => serialize('/tmp'),
      ],
    ];

    // The expected results will include only the first two files, since the
    // plugin will filter out files with either the null URI scheme or the
    // temporary scheme.
    $tests[0]['expected_data'] = array_slice($tests[0]['source_data']['file_managed'], 0, 2);

    // The filepath property will vary by URI scheme.
    $tests[0]['expected_data'][0]['filepath'] = 'sites/default/files/cube.jpeg';
    $tests[0]['expected_data'][1]['filepath'] = '/path/to/private/files/cube.jpeg';

    // Do an automatic count.
    $tests[0]['expected_count'] = NULL;

    // Set up plugin configuration.
    $tests[0]['configuration'] = [
      'constants' => [
        'source_base_path' => '/path/to/files',
      ],
      'scheme' => ['public', 'private', 'temporary'],
    ];

    // Test getting only public files.
    $tests[1]['source_data'] = $tests[0]['source_data'];

    $tests[1]['expected_data'] = [
      [
        'fid' => '1',
        'uid' => '1',
        'filename' => 'cube.jpeg',
        'uri' => 'public://cube.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => '3620',
        'status' => '1',
        'timestamp' => '1421727515',
        'filepath' => 'sites/default/files/cube.jpeg',
      ],
    ];
    // Do an automatic count.
    $tests[1]['expected_count'] = NULL;

    // Set up plugin configuration.
    $tests[1]['configuration'] = [
      'constants' => [
        'source_base_path' => '/path/to/files',
      ],
      'scheme' => ['public'],
    ];

    // Test getting only public files when configuration scheme is not an array.
    $tests[2]['source_data'] = $tests[0]['source_data'];

    $tests[2]['expected_data'] = [
      [
        'fid' => '1',
        'uid' => '1',
        'filename' => 'cube.jpeg',
        'uri' => 'public://cube.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => '3620',
        'status' => '1',
        'timestamp' => '1421727515',
        'filepath' => 'sites/default/files/cube.jpeg',
      ],
    ];
    // Do an automatic count.
    $tests[2]['expected_count'] = NULL;

    // Set up plugin configuration.
    $tests[2]['configuration'] = [
      'constants' => [
        'source_base_path' => '/path/to/files',
      ],
      'scheme' => 'public',
    ];

    return $tests;
  }

}

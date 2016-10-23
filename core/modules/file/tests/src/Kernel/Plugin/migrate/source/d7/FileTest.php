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

    // The expected results will include only the first three files, since we
    // are configuring the plugin to filter out the file with the null URI
    // scheme.
    $tests[0]['expected_data'] = array_slice($tests[0]['source_data']['file_managed'], 0, 3);

    // The filepath property will vary by URI scheme.
    $tests[0]['expected_data'][0]['filepath'] = 'sites/default/files/cube.jpeg';
    $tests[0]['expected_data'][1]['filepath'] = '/path/to/private/files/cube.jpeg';
    $tests[0]['expected_data'][2]['filepath'] = '/tmp/cube.jpeg';

    // Do an automatic count.
    $tests[0]['expected_count'] = NULL;

    // Set up plugin configuration.
    $tests[0]['configuration'] = [
      'constants' => [
        'source_base_path' => '/path/to/files',
      ],
      // Only return files which use one of these URI schemes.
      'scheme' => ['public', 'private', 'temporary'],
    ];

    return $tests;
  }

}

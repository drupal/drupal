<?php

namespace Drupal\Tests\file\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 file source plugin.
 *
 * @covers \Drupal\file\Plugin\migrate\source\d6\File
 *
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

    // The source data.
    $tests[0]['source_data']['files'] = [
      [
        'fid' => 1,
        'uid' => 1,
        'filename' => 'migrate-test-file-1.pdf',
        'filepath' => 'sites/default/files/migrate-test-file-1.pdf',
        'filemime' => 'application/pdf',
        'filesize' => 890404,
        'status' => 1,
        'timestamp' => 1382255613,
      ],
      [
        'fid' => 2,
        'uid' => 1,
        'filename' => 'migrate-test-file-2.pdf',
        'filepath' => 'sites/default/files/migrate-test-file-2.pdf',
        'filemime' => 'application/pdf',
        'filesize' => 204124,
        'status' => 1,
        'timestamp' => 1382255662,
      ],
    ];

    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = $tests[0]['source_data']['files'];

    return $tests;
  }

}

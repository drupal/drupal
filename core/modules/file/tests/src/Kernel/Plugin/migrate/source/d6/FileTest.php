<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\migrate\source\d6;

use Drupal\file\Plugin\migrate\source\d6\File;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests D6 file source plugin.
 */
#[CoversClass(File::class)]
#[Group('file')]
#[RunTestsInSeparateProcesses]
class FileTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
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
      [
        'fid' => 3,
        'uid' => 1,
        'filename' => 'migrate-test-file-3.pdf',
        'filepath' => '/tmp/migrate-test-file-3.pdf',
        'filemime' => 'application/pdf',
        'filesize' => 304124,
        'status' => 1,
        'timestamp' => 1382277662,
      ],
    ];

    // The expected results are the same as the source data but excluding
    // the temporary file.
    $tests[0]['expected_data'] = [
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
    return $tests;
  }

}

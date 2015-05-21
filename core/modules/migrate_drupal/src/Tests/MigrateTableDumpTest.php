<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\MigrateTableDumpTest.
 */

namespace Drupal\migrate_drupal\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Validates the table dumps.
 *
 * @group migrate_drupal
 */
class MigrateTableDumpTest extends KernelTestBase {

  protected function verifyDumpFiles($directory) {
    $tables = file_scan_directory($directory, '/.php$/');
    foreach ($tables as $table) {
      $contents = rtrim(file_get_contents($table->uri));
      $this->assertIdentical(substr($contents, -32), md5(substr($contents, 0, -33)), $table->uri);
    }
  }

  public function testMigrateDrupal6TableDumps() {
    $this->verifyDumpFiles(__DIR__ . '/Table/d6');
  }

  public function testMigrateDrupal7TableDumps() {
    $this->verifyDumpFiles(__DIR__ . '/Table/d7');
  }

}

<?php

/**
 * @file
 * Contains \Drupal\system\Tests\File\FileSaveHtaccessLoggingTest.
 */

namespace Drupal\system\Tests\File;

use Drupal\Component\PhpStorage\FileStorage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the log message added by file_save_htacess().
 *
 * @group File
 */
class FileSaveHtaccessLoggingTest extends WebTestBase {

  protected static $modules = ['dblog'];

  /**
   * Tests file_save_htaccess().
   */
  function testHtaccessSave() {
    // Prepare test directories.
    $private = $this->publicFilesDirectory . '/test/private';

    // Verify that file_save_htaccess() returns FALSE if .htaccess cannot be
    // written and writes a correctly formatted message to the error log. Set
    // $private to TRUE so all possible .htaccess lines are written.
    $this->assertFalse(file_save_htaccess($private, TRUE));
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/reports/dblog');
    $this->clickLink("Security warning: Couldn't write .htaccess file. Please…");

    $lines = FileStorage::htaccessLines(TRUE);
    foreach (array_filter(explode("\n", $lines)) as $line) {
      $this->assertEscaped($line);
    }
  }

}

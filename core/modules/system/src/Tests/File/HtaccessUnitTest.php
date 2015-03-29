<?php

/**
 * @file
 * Contains \Drupal\system\Tests\File\HtaccessUnitTest.
 */

namespace Drupal\system\Tests\File;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests .htaccess file saving.
 *
 * @group File
 */
class HtaccessUnitTest extends KernelTestBase {

  /**
   * Tests file_save_htaccess().
   */
  function testHtaccessSave() {
    // Prepare test directories.
    $public = $this->publicFilesDirectory . '/test/public';
    $private = $this->publicFilesDirectory . '/test/private';
    $stream = 'public://test/stream';

    // Verify that file_save_htaccess() returns FALSE if .htaccess cannot be
    // written.
    // Note: We cannot test the condition of a directory lacking write
    // permissions, since at least on Windows file_save_htaccess() succeeds
    // even when changing directory permissions to 0000.
    $this->assertFalse(file_save_htaccess($public, FALSE));

    // Create public .htaccess file.
    mkdir($public, 0777, TRUE);
    $this->assertTrue(file_save_htaccess($public, FALSE));
    $content = file_get_contents($public . '/.htaccess');
    $this->assertTrue(strpos($content, "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006") !== FALSE);
    $this->assertFalse(strpos($content, "Require all denied") !== FALSE);
    $this->assertFalse(strpos($content, "Deny from all") !== FALSE);
    $this->assertTrue(strpos($content, "Options None") !== FALSE);
    $this->assertTrue(strpos($content, "Options +FollowSymLinks") !== FALSE);
    $this->assertTrue(strpos($content, "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003") !== FALSE);
    $this->assertFilePermissions($public . '/.htaccess', 0444);

    $this->assertTrue(file_save_htaccess($public, FALSE));

    // Create private .htaccess file.
    mkdir($private, 0777, TRUE);
    $this->assertTrue(file_save_htaccess($private));
    $content = file_get_contents($private . '/.htaccess');
    $this->assertTrue(strpos($content, "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006") !== FALSE);
    $this->assertTrue(strpos($content, "Require all denied") !== FALSE);
    $this->assertTrue(strpos($content, "Deny from all") !== FALSE);
    $this->assertTrue(strpos($content, "Options None") !== FALSE);
    $this->assertTrue(strpos($content, "Options +FollowSymLinks") !== FALSE);
    $this->assertTrue(strpos($content, "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003") !== FALSE);
    $this->assertFilePermissions($private . '/.htaccess', 0444);

    $this->assertTrue(file_save_htaccess($private));

    // Create an .htaccess file using a stream URI.
    mkdir($stream, 0777, TRUE);
    $this->assertTrue(file_save_htaccess($stream));
    $content = file_get_contents($stream . '/.htaccess');
    $this->assertTrue(strpos($content,"SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006") !== FALSE);
    $this->assertTrue(strpos($content, "Require all denied") !== FALSE);
    $this->assertTrue(strpos($content,"Deny from all") !== FALSE);
    $this->assertTrue(strpos($content,"Options None") !== FALSE);
    $this->assertTrue(strpos($content,"Options +FollowSymLinks") !== FALSE);
    $this->assertTrue(strpos($content, "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2013_003") !== FALSE);
    $this->assertFilePermissions($stream . '/.htaccess', 0444);

    $this->assertTrue(file_save_htaccess($stream));
  }

  /**
   * Asserts expected file permissions for a given file.
   *
   * @param string $uri
   *   The URI of the file to check.
   * @param int $expected
   *   The expected file permissions; e.g., 0444.
   *
   * @return bool
   *   Whether the actual file permissions match the expected.
   */
  protected function assertFilePermissions($uri, $expected) {
    $actual = fileperms($uri) & 0777;
    return $this->assertIdentical($actual, $expected, SafeMarkup::format('@uri file permissions @actual are identical to @expected.', array(
      '@uri' => $uri,
      '@actual' => 0 . decoct($actual),
      '@expected' => 0 . decoct($expected),
    )));
  }

}

<?php

/**
 * @file
 * Contains Drupal\system\Tests\System\HtaccessTest
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests access restrictions provided by the default .htaccess file.
 *
 * @group system
 */
class HtaccessTest extends WebTestBase {
  /**
   * Get an array of file paths for access testing.
   *
   * @return array
   *   An array of file paths to be access-tested.
   */
  protected function getProtectedFiles() {
    $path = drupal_get_path('module', 'system') . '/tests/fixtures/HtaccessTest';
    $file_exts = [
      'engine',
      'inc',
      'install',
      'make',
      'module',
      'profile',
      'po',
      'sh',
      'sql',
      'theme',
      'twig',
      'tpl.php',
      'xtmpl',
      'yml',
    ];

    foreach ($file_exts as $file_ext) {
      $file_paths[] = "$path/access_test.$file_ext";
    }

    return $file_paths;
  }

  /**
   * Iterates over protected files and calls assertNoFileAccess().
   */
  public function testFileAccess() {
    foreach ($this->getProtectedFiles() as $file) {
      $this->assertNoFileAccess($file);
    }
  }

  /**
   * Asserts that a file exists but not accessible via HTTP.
   *
   * @param string $path
   *   Path to file. Without leading slash.
   */
  protected function assertNoFileAccess($path) {
    $this->assertTrue(file_exists(\Drupal::root() . '/' . $path));
    $this->drupalGet($path);
    $this->assertResponse(403);
  }

}

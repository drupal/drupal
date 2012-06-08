<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\CascadingStylesheetsUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * CSS Unit Tests.
 */
class CascadingStylesheetsUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'CSS Unit Tests',
      'description' => 'Unit tests on CSS functions like aggregation.',
      'group' => 'Common',
    );
  }

  /**
   * Tests basic CSS loading with and without optimization via drupal_load_stylesheet().
   *
   * Known tests:
   * - Retain white-space in selectors. (http://drupal.org/node/472820)
   * - Proper URLs in imported files. (http://drupal.org/node/265719)
   * - Retain pseudo-selectors. (http://drupal.org/node/460448)
   */
  function testLoadCssBasic() {
    // Array of files to test living in 'simpletest/files/css_test_files/'.
    // - Original: name.css
    // - Unoptimized expected content: name.css.unoptimized.css
    // - Optimized expected content: name.css.optimized.css
    $testfiles = array(
      'css_input_without_import.css',
      'css_input_with_import.css',
      'comment_hacks.css'
    );
    $path = drupal_get_path('module', 'simpletest') . '/files/css_test_files';
    foreach ($testfiles as $file) {
      $expected = file_get_contents("$path/$file.unoptimized.css");
      $unoptimized_output = drupal_load_stylesheet("$path/$file.unoptimized.css", FALSE);
      $this->assertEqual($unoptimized_output, $expected, t('Unoptimized CSS file has expected contents (@file)', array('@file' => $file)));

      $expected = file_get_contents("$path/$file.optimized.css");
      $optimized_output = drupal_load_stylesheet("$path/$file", TRUE);
      $this->assertEqual($optimized_output, $expected, t('Optimized CSS file has expected contents (@file)', array('@file' => $file)));

      // Repeat the tests by accessing the stylesheets by URL.
      $expected = file_get_contents("$path/$file.unoptimized.css");
      $unoptimized_output_url = drupal_load_stylesheet($GLOBALS['base_url'] . "/$path/$file.unoptimized.css", FALSE);
      $this->assertEqual($unoptimized_output, $expected, t('Unoptimized CSS file (loaded from an URL) has expected contents (@file)', array('@file' => $file)));

      $expected = file_get_contents("$path/$file.optimized.css");
      $optimized_output = drupal_load_stylesheet($GLOBALS['base_url'] . "/$path/$file", TRUE);
      $this->assertEqual($optimized_output, $expected, t('Optimized CSS file (loaded from an URL) has expected contents (@file)', array('@file' => $file)));
    }
  }
}

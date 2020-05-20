<?php

namespace Drupal\Tests\Composer\Plugin\Scaffold;

/**
 * Convenience class for creating fixtures.
 */
trait AssertUtilsTrait {

  /**
   * Asserts that a given file exists and is/is not a symlink.
   *
   * @param string $path
   *   The path to check exists.
   * @param bool $is_link
   *   Checks if the file should be a symlink or not.
   * @param string $contents_contains
   *   Regex to check the file contents.
   */
  protected function assertScaffoldedFile($path, $is_link, $contents_contains) {
    $this->assertFileExists($path);
    $contents = file_get_contents($path);
    $this->assertStringContainsString($contents_contains, basename($path) . ': ' . $contents);
    $this->assertSame($is_link, is_link($path));
  }

  /**
   * Asserts that a file does not exist or exists and does not contain a value.
   *
   * @param string $path
   *   The path to check exists.
   * @param string $contents_not_contains
   *   A string that is expected should NOT occur in the file contents.
   */
  protected function assertScaffoldedFileDoesNotContain($path, $contents_not_contains) {
    // If the file does not exist at all, we'll count that as a pass.
    if (!file_exists($path)) {
      return;
    }
    $contents = file_get_contents($path);
    $this->assertStringNotContainsString($contents_not_contains, $contents, basename($path) . ' contains unexpected contents:');
  }

}

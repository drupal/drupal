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
    $this->assertContains($contents_contains, basename($path) . ': ' . $contents);
    $this->assertSame($is_link, is_link($path));
  }

}

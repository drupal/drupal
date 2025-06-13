<?php

namespace Drupal\Core\Test\RunTests;

use PHPUnit\Framework\TestCase;

@trigger_error('Drupal\Core\Test\RunTests\TestFileParser is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3447698', E_USER_DEPRECATED);

/**
 * Parses class names from PHP files without loading them.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3447698
 *
 * @internal
 */
class TestFileParser {

  /**
   * Gets the classes from a PHP file.
   *
   * @param string $file
   *   The path to the file to parse.
   *
   * @return string[]
   *   Array of fully qualified class names within the PHP file.
   */
  public function getTestListFromFile($file) {
    $test_list = $this->parseContents(file_get_contents($file));
    return array_filter($test_list, function ($class) {
      return is_subclass_of($class, TestCase::class);
    });
  }

  /**
   * Parse class names out of PHP file contents.
   *
   * @param string $contents
   *   The contents of a PHP file.
   *
   * @return string[]
   *   Array of fully qualified class names within the PHP file contents.
   */
  protected function parseContents($contents) {
    // Extract a potential namespace.
    $namespace = FALSE;
    if (preg_match('@^\s*namespace ([^ ;]+)@m', $contents, $matches)) {
      $namespace = $matches[1];
    }
    $test_list = [];
    // Extract all class names. Abstract classes are excluded on purpose.
    preg_match_all('@^\s*(?!abstract\s+)(?:final\s+|\s*)class ([^ ]+)@m', $contents, $matches);
    if (!$namespace) {
      $test_list = $matches[1];
    }
    else {
      foreach ($matches[1] as $class_name) {
        $namespace_class = $namespace . '\\' . $class_name;
        $test_list[] = $namespace_class;
      }
    }
    return $test_list;
  }

}

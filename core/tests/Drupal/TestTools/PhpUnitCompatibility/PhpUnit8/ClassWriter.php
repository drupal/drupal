<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit8;

/**
 * Helper class to rewrite PHPUnit's TestCase class.
 *
 * This class contains static methods only and is not meant to be instantiated.
 *
 * @internal
 *   This should only be called by test running code. Drupal 9 will provide best
 *   effort to maintain this class for the Drupal 9 cycle. However if changes to
 *   PHP or PHPUnit make this impossible then support will be removed.
 */
final class ClassWriter {

  /**
   * This class should not be instantiated.
   */
  private function __construct() {
  }

  /**
   * Mutates the TestCase class from PHPUnit to make it compatible with Drupal.
   *
   * @param \Composer\Autoload\ClassLoader $autoloader
   *   The autoloader.
   *
   * @throws \ReflectionException
   */
  public static function mutateTestBase($autoloader) {
    // If the class exists already there is nothing we can do. Hopefully this
    // is happening because this has been called already. The call from
    // \Drupal\Core\Test\TestDiscovery::registerTestNamespaces() necessitates
    // this protection.
    if (class_exists('PHPUnit\Framework\TestCase', FALSE)) {
      return;
    }
    // Inspired by Symfony's simple-phpunit remove typehints from TestCase.
    $alteredFile = $autoloader->findFile('PHPUnit\Framework\TestCase');
    $phpunit_dir = dirname($alteredFile, 3);
    // Mutate TestCase code to make it compatible with Drupal 8 and 9 tests.
    $alteredCode = file_get_contents($alteredFile);
    $alteredCode = preg_replace('/^    ((?:protected|public)(?: static)? function \w+\(\)): void/m', '    $1', $alteredCode);
    $alteredCode = str_replace("__DIR__ . '/../Util/", "'$phpunit_dir/src/Util/", $alteredCode);
    $simpletest_directory = __DIR__ . '/../../../../../../sites/simpletest';
    // Only write when necessary.
    $filename = $simpletest_directory . '/TestCase.php';
    if (!file_exists($filename) || md5_file($filename) !== md5($alteredCode)) {
      // Create directory when necessary.
      if (!file_exists($simpletest_directory)) {
        mkdir($simpletest_directory, 0777, TRUE);
      }
      file_put_contents($filename, $alteredCode);
    }
    include $filename;
  }

}

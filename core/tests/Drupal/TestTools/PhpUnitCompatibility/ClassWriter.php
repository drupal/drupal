<?php

declare(strict_types=1);

namespace Drupal\TestTools\PhpUnitCompatibility;

use Composer\Autoload\ClassLoader;

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
   * Mutates PHPUnit classes to make it compatible with Drupal.
   *
   * @param \Composer\Autoload\ClassLoader $autoloader
   *   The autoloader.
   *
   * @throws \ReflectionException
   */
  public static function mutateTestBase($autoloader) {
    static::alterAssert($autoloader);
    static::alterTestCase($autoloader);
  }

  /**
   * Alters the Assert class.
   *
   * @param \Composer\Autoload\ClassLoader $autoloader
   *   The autoloader.
   *
   * @throws \ReflectionException
   */
  private static function alterAssert(ClassLoader $autoloader): void {
    // If the class exists already there is nothing we can do. Hopefully this
    // is happening because this has been called already. The call from
    // \Drupal\Core\Test\TestDiscovery::registerTestNamespaces() necessitates
    // this protection.
    if (class_exists('PHPUnit\Framework\Assert', FALSE)) {
      return;
    }
    // Mutate Assert code to make it forward compatible with different PhpUnit
    // versions, by adding Symfony's PHPUnit-bridge PolyfillAssertTrait.
    $alteredFile = $autoloader->findFile('PHPUnit\Framework\Assert');
    $phpunit_dir = dirname($alteredFile, 3);
    $alteredCode = file_get_contents($alteredFile);
    $alteredCode = preg_replace('/abstract class Assert[^\{]+\{/', '$0 ' . \PHP_EOL . "    use \Symfony\Bridge\PhpUnit\Legacy\PolyfillAssertTrait;" . \PHP_EOL, $alteredCode, 1);
    include static::flushAlteredCodeToFile('Assert.php', $alteredCode);
  }

  /**
   * Alters the TestCase class.
   *
   * @param \Composer\Autoload\ClassLoader $autoloader
   *   The autoloader.
   *
   * @throws \ReflectionException
   */
  private static function alterTestCase(ClassLoader $autoloader): void {
    // If the class exists already there is nothing we can do. Hopefully this
    // is happening because this has been called already. The call from
    // \Drupal\Core\Test\TestDiscovery::registerTestNamespaces() necessitates
    // this protection.
    if (class_exists('PHPUnit\Framework\TestCase', FALSE)) {
      return;
    }
    // Mutate TestCase code to make it forward compatible with different PhpUnit
    // versions, by adding Symfony's PHPUnit-bridge PolyfillTestCaseTrait.
    $alteredFile = $autoloader->findFile('PHPUnit\Framework\TestCase');
    $phpunit_dir = dirname($alteredFile, 3);
    $alteredCode = file_get_contents($alteredFile);
    $alteredCode = preg_replace('/abstract class TestCase[^\{]+\{/', '$0 ' . \PHP_EOL . "    use \Symfony\Bridge\PhpUnit\Legacy\PolyfillTestCaseTrait;" . \PHP_EOL, $alteredCode, 1);
    $alteredCode = str_replace("__DIR__ . '/../Util/", "'$phpunit_dir/src/Util/", $alteredCode);
    include static::flushAlteredCodeToFile('TestCase.php', $alteredCode);
  }

  /**
   * Flushes altered class code to file when necessary.
   *
   * @param string $file_name
   *   The file name.
   * @param string $altered_code
   *   The altered code.
   *
   * @return string
   *   The full path of the file to be included.
   */
  private static function flushAlteredCodeToFile(string $file_name, string $altered_code): string {
    $directory = __DIR__ . '/../../../../../sites/simpletest';
    $full_path = $directory . '/' . $file_name;

    // Only write when necessary.
    if (!file_exists($full_path) || md5_file($full_path) !== md5($altered_code)) {
      // Create directory when necessary.
      if (!is_dir($directory) && !@mkdir($directory, 0777, TRUE) && !is_dir($directory)) {
        throw new \RuntimeException('Unable to create directory: ' . $directory);
      }
      file_put_contents($full_path, $altered_code);
    }

    return $full_path;
  }

}

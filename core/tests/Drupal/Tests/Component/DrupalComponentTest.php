<?php

declare(strict_types=1);

namespace Drupal\Tests\Component;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * General tests for \Drupal\Component that can't go anywhere else.
 *
 * @group Component
 */
class DrupalComponentTest extends TestCase {

  /**
   * Tests that classes in Component do not use any Core class.
   */
  public function testNoCoreInComponent(): void {
    $component_path = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))) . '/lib/Drupal/Component';
    foreach ($this->findPhpClasses($component_path) as $class) {
      $this->assertNoCoreUsage($class);
    }
  }

  /**
   * Tests that classes in Component Tests do not use any Core class.
   */
  public function testNoCoreInComponentTests(): void {
    $component_path = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))) . '/tests/Drupal/Tests/Component';
    foreach ($this->findPhpClasses($component_path) as $class) {
      $this->assertNoCoreUsage($class);
    }
  }

  /**
   * Tests LICENSE.txt is present and has the correct content.
   *
   * @param string $component_path
   *   The path to the component.
   *
   * @dataProvider getComponents
   */
  public function testComponentLicense(string $component_path): void {
    $this->assertFileExists($component_path . DIRECTORY_SEPARATOR . 'LICENSE.txt');
    $this->assertSame('e84dac1d9fbb5a4a69e38654ce644cea769aa76b', hash_file('sha1', $component_path . DIRECTORY_SEPARATOR . 'LICENSE.txt'));
  }

  /**
   * Data provider.
   *
   * @return array
   *   An associative array where the keys are component names and the values
   *   are arrays containing the corresponding component path.
   */
  public static function getComponents(): array {
    $root_component_path = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))) . '/lib/Drupal/Component';
    $component_paths = [];
    foreach (new \DirectoryIterator($root_component_path) as $file) {
      if ($file->isDir() && !$file->isDot()) {
        $component_paths[$file->getBasename()] = [$file->getPathname()];
      }
    }
    return $component_paths;
  }

  /**
   * Searches a directory recursively for PHP classes.
   *
   * @param string $dir
   *   The full path to the directory that should be checked.
   *
   * @return array
   *   An array of class paths.
   */
  protected function findPhpClasses($dir): array {
    $classes = [];
    foreach (new \DirectoryIterator($dir) as $file) {
      if ($file->isDir() && !$file->isDot()) {
        $classes = array_merge($classes, $this->findPhpClasses($file->getPathname()));
      }
      elseif ($file->getExtension() == 'php') {
        $classes[] = $file->getPathname();
      }
    }

    return $classes;
  }

  /**
   * Asserts that the given class is not using any class from Core namespace.
   *
   * @param string $class_path
   *   The full path to the class that should be checked.
   *
   * @internal
   */
  protected function assertNoCoreUsage(string $class_path): void {
    $contents = file_get_contents($class_path);
    preg_match_all('/^.*Drupal\\\Core.*$/m', $contents, $matches);
    $matches = array_filter($matches[0], function ($line) {
      // Filter references that don't really matter.
      return preg_match('/@see|E_USER_DEPRECATED|expectDeprecation/', $line) === 0;
    });
    $this->assertEmpty($matches, "Checking for illegal reference to 'Drupal\\Core' namespace in $class_path");
  }

  /**
   * Data provider for testAssertNoCoreUsage().
   *
   * @return array
   *   Data for testAssertNoCoreUsage() in the form:
   *   - TRUE if the test passes, FALSE otherwise.
   *   - File data as a string. This will be used as a virtual file.
   */
  public static function providerAssertNoCoreUsage() {
    return [
      [
        TRUE,
        '@see \\Drupal\\Core\\Something',
      ],
      [
        FALSE,
        '\\Drupal\\Core\\Something',
      ],
      [
        FALSE,
        "@see \\Drupal\\Core\\Something\n" .
        '\\Drupal\\Core\\Something',
      ],
      [
        FALSE,
        "\\Drupal\\Core\\Something\n" .
        '@see \\Drupal\\Core\\Something',
      ],
    ];
  }

  /**
   * @covers \Drupal\Tests\Component\DrupalComponentTest::assertNoCoreUsage
   * @dataProvider providerAssertNoCoreUsage
   */
  public function testAssertNoCoreUsage($expected_pass, $file_data): void {
    // Set up a virtual file to read.
    $vfs_root = vfsStream::setup('root');
    vfsStream::newFile('Test.php')->at($vfs_root)->setContent($file_data);
    $file_uri = vfsStream::url('root/Test.php');

    try {
      $pass = TRUE;
      $this->assertNoCoreUsage($file_uri);
    }
    catch (AssertionFailedError) {
      $pass = FALSE;
    }
    $this->assertEquals($expected_pass, $pass, $expected_pass ?
      'Test caused a false positive' :
      'Test failed to detect Core usage');
  }

}

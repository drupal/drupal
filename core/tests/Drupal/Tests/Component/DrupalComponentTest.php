<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\DrupalComponentTest.
 */

namespace Drupal\Tests\Component;

use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * General tests for \Drupal\Component that can't go anywhere else.
 *
 * @group Component
 */
class DrupalComponentTest extends UnitTestCase {

  /**
   * Tests that classes in Component do not use any Core class.
   */
  public function testNoCoreInComponent() {
    $component_path = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))) . '/lib/Drupal/Component';
    foreach ($this->findPhpClasses($component_path) as $class) {
      $this->assertNoCoreUsage($class);
    }
  }

  /**
   * Tests that classes in Component Tests do not use any Core class.
   */
  public function testNoCoreInComponentTests() {
    $component_path = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))) . '/tests/Drupal/Tests/Component';
    foreach ($this->findPhpClasses($component_path) as $class) {
      $this->assertNoCoreUsage($class);
    }
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
  protected function findPhpClasses($dir) {
    $classes = array();
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
   */
  protected function assertNoCoreUsage($class_path) {
    $contents = file_get_contents($class_path);
    preg_match_all('/^.*Drupal\\\Core.*$/m', $contents, $matches);
    $matches = array_filter($matches[0], function($line) {
      // Filter references to @see as they don't really matter.
      return strpos($line, '@see') === FALSE;
    });
    $this->assertEmpty($matches, "Checking for illegal reference to 'Drupal\\Core' namespace in $class_path");
  }

  /**
   * Data provider for testAssertNoCoreUseage().
   *
   * @return array
   *   Data for testAssertNoCoreUseage() in the form:
   *   - TRUE if the test passes, FALSE otherwise.
   *   - File data as a string. This will be used as a virtual file.
   */
  public function providerAssertNoCoreUseage() {
    return array(
      array(
        TRUE,
        '@see \\Drupal\\Core\\Something',
      ),
      array(
        FALSE,
        '\\Drupal\\Core\\Something',
      ),
      array(
        FALSE,
        "@see \\Drupal\\Core\\Something\n" .
        '\\Drupal\\Core\\Something',
      ),
      array(
        FALSE,
        "\\Drupal\\Core\\Something\n" .
        '@see \\Drupal\\Core\\Something',
      ),
    );
  }

  /**
   * @covers \Drupal\Tests\Component\DrupalComponentTest::assertNoCoreUsage
   * @dataProvider providerAssertNoCoreUseage
   */
  public function testAssertNoCoreUseage($expected_pass, $file_data) {
    // Set up a virtual file to read.
    $vfs_root = vfsStream::setup('root');
    vfsStream::newFile('Test.php')->at($vfs_root)->setContent($file_data);
    $file_uri = vfsStream::url('root/Test.php');

    try {
      $pass = true;
      $this->assertNoCoreUsage($file_uri);
    }
    catch (\PHPUnit_Framework_AssertionFailedError $e) {
      $pass = false;
    }
    $this->assertEquals($expected_pass, $pass, $expected_pass ?
      'Test caused a false positive' :
      'Test failed to detect Core usage');
  }

}

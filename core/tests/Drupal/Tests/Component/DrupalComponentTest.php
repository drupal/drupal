<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\DrupalComponentTest.
 */

namespace Drupal\Tests\Component;

use Drupal\Tests\UnitTestCase;

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
    foreach (new \SplFileObject($class_path) as $line_number => $line) {
      // Allow linking to Core files with @see docs. Its harmless and boosts DX
      // because even outside projects can treat those links as examples.
      if ($line && (strpos($line, '@see ') === FALSE)) {
        $this->assertSame(FALSE, strpos($line, 'Drupal\\Core'), "Illegal reference to 'Drupal\\Core' namespace in $class_path at line $line_number");
      }
    }
  }

}

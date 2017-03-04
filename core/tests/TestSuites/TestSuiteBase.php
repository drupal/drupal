<?php

namespace Drupal\Tests\TestSuites;
use Drupal\simpletest\TestDiscovery;

/**
 * Base class for Drupal test suites.
 */
abstract class TestSuiteBase extends \PHPUnit_Framework_TestSuite {

  /**
   * Finds extensions in a Drupal installation.
   *
   * An extension is defined as a directory with an *.info.yml file in it.
   *
   * @param string $root
   *   Path to the root of the Drupal installation.
   *
   * @return string[]
   *   Associative array of extension paths, with extension name as keys.
   */
  protected function findExtensionDirectories($root) {
    $extension_roots = \drupal_phpunit_contrib_extension_directory_roots($root);

    $extension_directories = array_map('drupal_phpunit_find_extension_directories', $extension_roots);
    return array_reduce($extension_directories, 'array_merge', []);
  }

  /**
   * Find and add tests to the suite for core and any extensions.
   *
   * @param string $root
   *   Path to the root of the Drupal installation.
   * @param string $suite_namespace
   *   SubNamespace used to separate test suite. Examples: Unit, Functional.
   */
  protected function addTestsBySuiteNamespace($root, $suite_namespace) {
    // Core's tests are in the namespace Drupal\${suite_namespace}Tests\ and are
    // always inside of core/tests/Drupal/${suite_namespace}Tests. The exception
    // to this is Unit tests for historical reasons.
    if ($suite_namespace == 'Unit') {
      $this->addTestFiles(TestDiscovery::scanDirectory("Drupal\\Tests\\", "$root/core/tests/Drupal/Tests"));
    }
    else {
      $this->addTestFiles(TestDiscovery::scanDirectory("Drupal\\${suite_namespace}Tests\\", "$root/core/tests/Drupal/${suite_namespace}Tests"));
    }

    // Extensions' tests will always be in the namespace
    // Drupal\Tests\$extension_name\$suite_namespace\ and be in the
    // $extension_path/tests/src/$suite_namespace directory. Not all extensions
    // will have all kinds of tests.
    foreach ($this->findExtensionDirectories($root) as $extension_name => $dir) {
      $test_path = "$dir/tests/src/$suite_namespace";
      if (is_dir($test_path)) {
        $this->addTestFiles(TestDiscovery::scanDirectory("Drupal\\Tests\\$extension_name\\$suite_namespace\\", $test_path));
      }
    }
  }

}

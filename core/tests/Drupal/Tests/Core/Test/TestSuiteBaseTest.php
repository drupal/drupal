<?php

namespace Drupal\Tests\Core\Test;

use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;
use Drupal\Tests\TestSuites\TestSuiteBase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

// The test suite class is not part of the autoloader, we need to include it
// manually.
require_once __DIR__ . '/../../../../TestSuites/TestSuiteBase.php';

// In order to manage different method signatures between PHPUnit versions, we
// dynamically load a compatibility trait dependent on the PHPUnit runner
// version.
if (!trait_exists(PhpunitVersionDependentStubTestSuiteBaseTrait::class, FALSE)) {
  class_alias("Drupal\TestTools\PhpUnitCompatibility\PhpUnit" . RunnerVersion::getMajor() . "\StubTestSuiteBaseTrait", PhpunitVersionDependentStubTestSuiteBaseTrait::class);
}

/**
 * @coversDefaultClass \Drupal\Tests\TestSuites\TestSuiteBase
 *
 * @group TestSuite
 */
class TestSuiteBaseTest extends TestCase {

  /**
   * Helper method to set up the file system.
   *
   * @return array[]
   *   A Drupal filesystem suitable for use with vfsStream.
   */
  protected function getFilesystem() {
    return [
      'core' => [
        'modules' => [],
        'profiles' => [],
        'tests' => [
          'Drupal' => [
            'NotUnitTests' => [
              'CoreNotUnitTest.php' => '<?php',
            ],
            'Tests' => [
              'CoreUnitTest.php' => '<?php',
              // Ensure that the following files are not found as tests.
              'Listeners' => [
                'Listener.php' => '<?php',
                'Legacy' => [
                  'Listener.php' => '<?php',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @return array[]
   *   Test data for testAddTestsBySuiteNamespaceCore(). An array of arrays:
   *   - A filesystem array for vfsStream.
   *   - The sub-namespace of the test suite.
   *   - The array of tests expected to be discovered.
   */
  public function provideCoreTests() {
    $filesystem = $this->getFilesystem();
    return [
      'unit-tests' => [
        $filesystem,
        'Unit',
        [
          'Drupal\Tests\CoreUnitTest' => 'vfs://root/core/tests/Drupal/Tests/CoreUnitTest.php',
        ],
      ],
      'not-unit-tests' => [
        $filesystem,
        'NotUnit',
        [
          'Drupal\NotUnitTests\CoreNotUnitTest' => 'vfs://root/core/tests/Drupal/NotUnitTests/CoreNotUnitTest.php',
        ],
      ],
    ];
  }

  /**
   * Tests for special case behavior of unit test suite namespaces in core.
   *
   * @covers ::addTestsBySuiteNamespace
   *
   * @dataProvider provideCoreTests
   */
  public function testAddTestsBySuiteNamespaceCore($filesystem, $suite_namespace, $expected_tests) {
    // Set up the file system.
    $vfs = vfsStream::setup('root');
    vfsStream::create($filesystem, $vfs);

    // Make a stub suite base to test.
    $stub = new StubTestSuiteBase('test_me');

    // Access addTestsBySuiteNamespace().
    $ref_add_tests = new \ReflectionMethod($stub, 'addTestsBySuiteNamespace');
    $ref_add_tests->setAccessible(TRUE);

    // Invoke addTestsBySuiteNamespace().
    $ref_add_tests->invokeArgs($stub, [vfsStream::url('root'), $suite_namespace]);

    // Determine if we loaded the expected test files.
    $this->assertEquals($expected_tests, $stub->testFiles);
  }

  /**
   * Tests the assumption that local time is in 'Australia/Sydney'.
   */
  public function testLocalTimeZone() {
    // The 'Australia/Sydney' time zone is set in core/tests/bootstrap.php
    $this->assertEquals('Australia/Sydney', date_default_timezone_get());
  }

}

/**
 * Stub subclass of TestSuiteBase.
 *
 * We use this class to alter the behavior of TestSuiteBase so it can be
 * testable.
 */
class StubTestSuiteBase extends TestSuiteBase {

  use PhpunitVersionDependentStubTestSuiteBaseTrait;

  /**
   * Test files discovered by addTestsBySuiteNamespace().
   *
   * @var string[]
   */
  public $testFiles = [];

  /**
   * {@inheritdoc}
   */
  protected function findExtensionDirectories($root) {
    // We have to stub findExtensionDirectories() because we can't inject a
    // vfsStream filesystem into drupal_phpunit_find_extension_directories(),
    // which uses \SplFileInfo->getRealPath(). getRealPath() resolves
    // stream-based paths to an empty string. See
    // https://github.com/mikey179/vfsStream/wiki/Known-Issues
    return [];
  }

}

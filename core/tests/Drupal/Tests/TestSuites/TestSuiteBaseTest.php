<?php

namespace Drupal\Tests\TestSuites;

use org\bovigo\vfs\vfsStream;

// The test suite class is not part of the autoloader, we need to include it
// manually.
require_once __DIR__ . '/../../../TestSuites/TestSuiteBase.php';

/**
 * @coversDefaultClass \Drupal\Tests\TestSuites\TestSuiteBase
 *
 * @group TestSuite
 */
class TestSuiteBaseTest extends \PHPUnit_Framework_TestCase {

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
    $this->assertNotEmpty($stub->testFiles);
    $this->assertEmpty(array_diff_assoc($expected_tests, $stub->testFiles));
  }

}

/**
 * Stub subclass of TestSuiteBase.
 *
 * We use this class to alter the behavior of TestSuiteBase so it can be
 * testable.
 */
class StubTestSuiteBase extends TestSuiteBase {

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

  /**
   * {@inheritdoc}
   */
  public function addTestFiles($filenames) {
    // We stub addTestFiles() because the parent implementation can't deal with
    // vfsStream-based filesystems due to an error in
    // stream_resolve_include_path(). See
    // https://github.com/mikey179/vfsStream/issues/5 Here we just store the
    // test file being added in $this->testFiles.
    $this->testFiles = array_merge($this->testFiles, $filenames);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\TestSuites\TestSuiteBase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

// The test suite class is not part of the autoloader, we need to include it
// manually.
require_once __DIR__ . '/../../../../TestSuites/TestSuiteBase.php';

/**
 * @coversDefaultClass \Drupal\Tests\TestSuites\TestSuiteBase
 *
 * @group TestSuite
 */
class TestSuiteBaseTest extends TestCase {

  use ExpectDeprecationTrait;

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
   * @group legacy
   *
   * @covers ::addTestsBySuiteNamespace
   *
   * @dataProvider provideCoreTests
   */
  public function testAddTestsBySuiteNamespaceCore($filesystem, $suite_namespace, $expected_tests): void {

    $this->expectDeprecation('Drupal\\Tests\\Core\\Test\\StubTestSuiteBase is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement and test discovery will be handled differently in PHPUnit 10. See https://www.drupal.org/node/3405829');

    // Set up the file system.
    $vfs = vfsStream::setup('root');
    vfsStream::create($filesystem, $vfs);

    // Make a stub suite base to test.
    $stub = new StubTestSuiteBase('test_me');

    // Access addTestsBySuiteNamespace().
    $ref_add_tests = new \ReflectionMethod($stub, 'addTestsBySuiteNamespace');

    // Invoke addTestsBySuiteNamespace().
    $ref_add_tests->invokeArgs($stub, [vfsStream::url('root'), $suite_namespace]);

    // Determine if we loaded the expected test files.
    $this->assertEquals($expected_tests, $stub->testFiles);
  }

  /**
   * Tests the assumption that local time is in 'Australia/Sydney'.
   */
  public function testLocalTimeZone(): void {
    // The 'Australia/Sydney' time zone is set in core/tests/bootstrap.php
    $this->assertEquals('Australia/Sydney', date_default_timezone_get());
  }

}

/**
 * Stub subclass of TestSuiteBase.
 *
 * We use this class to alter the behavior of TestSuiteBase so it can be
 * testable.
 *
 * @phpstan-ignore-next-line
 */
class StubTestSuiteBase extends TestSuiteBase {

  /**
   * Test files discovered by addTestsBySuiteNamespace().
   *
   * @var string[]
   */
  public $testFiles = [];

  public function __construct(string $name) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement and test discovery will be handled differently in PHPUnit 10. See https://www.drupal.org/node/3405829', E_USER_DEPRECATED);
    // @phpstan-ignore-next-line
    parent::__construct($name);
  }

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
  public function addTestFiles($filenames): void {
    // We stub addTestFiles() because the parent implementation can't deal with
    // vfsStream-based filesystems due to an error in
    // stream_resolve_include_path(). See
    // https://github.com/mikey179/vfsStream/issues/5 Here we just store the
    // test file being added in $this->testFiles.
    $this->testFiles = array_merge($this->testFiles, $filenames);
  }

}

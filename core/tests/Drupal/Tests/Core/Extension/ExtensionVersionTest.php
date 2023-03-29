<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\ExtensionVersion;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ExtensionVersion
 *
 * @group Extension
 */
class ExtensionVersionTest extends UnitTestCase {

  /**
   * @covers ::getMajorVersion
   *
   * @dataProvider providerVersionInfos
   *
   * @param string $version
   *   The version string to test.
   * @param array $expected_version_info
   *   The expected version information.
   */
  public function testGetMajorVersion(string $version, array $expected_version_info): void {
    $version = ExtensionVersion::createFromVersionString($version);
    $this->assertSame($expected_version_info['major'], $version->getMajorVersion());
  }

  /**
   * @covers ::getMinorVersion
   *
   * @dataProvider providerVersionInfos
   *
   * @param string $version
   *   The version string to test.
   * @param array $expected_version_info
   *   The expected version information.
   */
  public function testGetMinorVersion(string $version, array $expected_version_info): void {
    $version = ExtensionVersion::createFromVersionString($version);
    $this->assertSame($expected_version_info['minor'], $version->getMinorVersion());
  }

  /**
   * @covers ::getVersionExtra
   *
   * @dataProvider providerVersionInfos
   *
   * @param string $version
   *   The version string to test.
   * @param array $expected_version_info
   *   The expected version information.
   */
  public function testGetVersionExtra(string $version, array $expected_version_info): void {
    $version = ExtensionVersion::createFromVersionString($version);
    $this->assertSame($expected_version_info['extra'], $version->getVersionExtra());
  }

  /**
   * Data provider for expected version information.
   *
   * @return mixed[][]
   *   Arrays of version information.
   */
  public function providerVersionInfos(): array {
    // Data provider values are:
    // - The version number to test.
    // - Array of expected version information with the following keys:
    //   -'major': The expected result from ::getMajorVersion().
    //   -'extra': The expected result from ::getVersionExtra().
    return [
      '8.x-1.3' => [
        '8.x-1.3',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => NULL,
        ],
      ],
      '8.x-1.0' => [
        '8.x-1.0',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => NULL,
        ],
      ],
      '8.x-1.0-alpha1' => [
        '8.x-1.0-alpha1',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => 'alpha1',
        ],
      ],
      '8.x-1.3-alpha1' => [
        '8.x-1.3-alpha1',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => 'alpha1',
        ],
      ],
      '0.1' => [
        '0.1',
        [
          'major' => '0',
          'minor' => NULL,
          'extra' => NULL,
        ],
      ],
      '1.0' => [
        '1.0',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => NULL,
        ],
      ],
      '1.3' => [
        '1.3',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => NULL,
        ],
      ],
      '1.0-alpha1' => [
        '1.0-alpha1',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => 'alpha1',
        ],
      ],
      '1.3-alpha1' => [
        '1.3-alpha1',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => 'alpha1',
        ],
      ],
      '0.2.0' => [
        '0.2.0',
        [
          'major' => '0',
          'minor' => '2',
          'extra' => NULL,
        ],
      ],
      '1.2.0' => [
        '1.2.0',
        [
          'major' => '1',
          'minor' => '2',
          'extra' => NULL,
        ],
      ],
      '1.0.3' => [
        '1.0.3',
        [
          'major' => '1',
          'minor' => '0',
          'extra' => NULL,
        ],
      ],
      '1.2.3' => [
        '1.2.3',
        [
          'major' => '1',
          'minor' => '2',
          'extra' => NULL,
        ],
      ],
      '1.2.0-alpha1' => [
        '1.2.0-alpha1',
        [
          'major' => '1',
          'minor' => '2',
          'extra' => 'alpha1',
        ],
      ],
      '1.2.3-alpha1' => [
        '1.2.3-alpha1',
        [
          'major' => '1',
          'minor' => '2',
          'extra' => 'alpha1',
        ],
      ],
      '8.x-1.x-dev' => [
        '8.x-1.x-dev',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => 'dev',
        ],
      ],
      '8.x-8.x-dev' => [
        '8.x-8.x-dev',
        [
          'major' => '8',
          'minor' => NULL,
          'extra' => 'dev',
        ],
      ],
      '1.x-dev' => [
        '1.x-dev',
        [
          'major' => '1',
          'minor' => NULL,
          'extra' => 'dev',
        ],
      ],
      '8.x-dev' => [
        '8.x-dev',
        [
          'major' => '8',
          'minor' => NULL,
          'extra' => 'dev',
        ],
      ],
      '1.0.x-dev' => [
        '1.0.x-dev',
        [
          'major' => '1',
          'minor' => '0',
          'extra' => 'dev',
        ],
      ],
      '1.2.x-dev' => [
        '1.2.x-dev',
        [
          'major' => '1',
          'minor' => '2',
          'extra' => 'dev',
        ],
      ],
    ];
  }

  /**
   * @covers ::createFromVersionString
   *
   * @dataProvider providerInvalidVersionNumber
   *
   * @param string $version
   *   The version string to test.
   */
  public function testInvalidVersionNumber(string $version): void {
    $this->expectException(\UnexpectedValueException::class);
    $this->expectExceptionMessage("Unexpected version number in: $version");
    ExtensionVersion::createFromVersionString($version);
  }

  /**
   * Data provider for testInvalidVersionNumber().
   *
   * @return string[]
   *   The test cases for testInvalidVersionNumber().
   */
  public function providerInvalidVersionNumber(): array {
    return static::createKeyedTestCases([
      '',
      '8',
      'x',
      'xx',
      '8.x-',
      '8.x',
      '.x',
      '.0',
      '.1',
      '.1.0',
      '1.0.',
      'x.1',
      '1.x.0',
      '1.1.x',
      '1.1.x-extra',
      'x.1.1',
      '1.1.1.1',
      '1.1.1.0',
    ]);
  }

  /**
   * @covers ::createFromVersionString
   *
   * @dataProvider providerInvalidVersionCorePrefix
   *
   * @param string $version
   *   The version string to test.
   */
  public function testInvalidVersionCorePrefix(string $version): void {
    $this->expectException(\UnexpectedValueException::class);
    $this->expectExceptionMessage("Unexpected version core prefix in $version. The only core prefix expected in \Drupal\Core\Extension\ExtensionVersion is: 8.x-");
    ExtensionVersion::createFromVersionString($version);
  }

  /**
   * Data provider for testInvalidVersionCorePrefix().
   *
   * @return string[]
   *   The test cases for testInvalidVersionCorePrefix().
   */
  public function providerInvalidVersionCorePrefix(): array {
    return static::createKeyedTestCases([
      '6.x-1.0',
      '7.x-1.x',
      '9.x-1.x',
      '10.x-1.x',
    ]);
  }

  /**
   * @covers ::createFromSupportBranch
   *
   * @dataProvider providerInvalidBranchCorePrefix
   *
   * @param string $branch
   *   The branch to test.
   */
  public function testInvalidBranchCorePrefix(string $branch): void {
    $this->expectException(\UnexpectedValueException::class);
    $this->expectExceptionMessage("Unexpected version core prefix in {$branch}0. The only core prefix expected in \Drupal\Core\Extension\ExtensionVersion is: 8.x-");
    ExtensionVersion::createFromSupportBranch($branch);
  }

  /**
   * Data provider for testInvalidBranchCorePrefix().
   *
   * @return string[]
   *   The test cases for testInvalidBranchCorePrefix().
   */
  public function providerInvalidBranchCorePrefix(): array {
    return static::createKeyedTestCases([
      '6.x-1.',
      '7.x-1.',
      '9.x-1.',
      '10.x-1.',
    ]);
  }

  /**
   * @covers ::createFromSupportBranch
   *
   * @dataProvider providerCreateFromSupportBranch
   *
   * @param string $branch
   *   The branch to test.
   * @param string $expected_major
   *   The expected major version.
   */
  public function testCreateFromSupportBranch(string $branch, string $expected_major): void {
    $version = ExtensionVersion::createFromSupportBranch($branch);
    $this->assertInstanceOf(ExtensionVersion::class, $version);
    $this->assertSame($expected_major, $version->getMajorVersion());
    // Version extra can't be determined from a branch.
    $this->assertNull($version->getVersionExtra());
  }

  /**
   * Data provider for testCreateFromSupportBranch().
   *
   * @return string[][]
   *   The test cases for testCreateFromSupportBranch().
   */
  public function providerCreateFromSupportBranch(): array {
    // Data provider values are:
    // - The version number to test.
    // - Array of expected version information with the following keys:
    //   -'major': The expected result from ::getMajorVersion().
    //   -'extra': The expected result from ::getVersionExtra().
    return [
      '0.' => [
        '0.',
        '0',
      ],
      '1.' => [
        '1.',
        '1',
      ],
      '0.1.' => [
        '0.1.',
        '0',
      ],
      '1.2.' => [
        '1.2.',
        '1',
      ],
      '8.x-1.' => [
        '8.x-1.',
        '1',
      ],
    ];
  }

  /**
   * @covers ::createFromSupportBranch
   *
   * @dataProvider provideInvalidBranch
   *
   * @param string $branch
   *   The branch to test.
   */
  public function testInvalidBranch(string $branch): void {
    $this->expectException(\UnexpectedValueException::class);
    $this->expectExceptionMessage("Invalid support branch: $branch");
    ExtensionVersion::createFromSupportBranch($branch);
  }

  /**
   * Data provider for testInvalidBranch().
   *
   * @return string[]
   *   The test cases for testInvalidBranch().
   */
  public function provideInvalidBranch(): array {
    return self::createKeyedTestCases([
      '8.x-1.0',
      '8.x-2.x',
      '2.x-1.0',
      '1.1',
      '1.x',
      '1.1.x',
      '1.1.1',
      '1.1.1.1',
    ]);
  }

  /**
   * Creates test case arrays for data provider methods.
   *
   * @param string[] $test_arguments
   *   The test arguments.
   *
   * @return mixed[]
   *   An array with $test_arguments as keys and each element of $test_arguments
   *   as a single item array
   */
  protected static function createKeyedTestCases(array $test_arguments): array {
    return array_combine(
      $test_arguments,
      array_map(function ($test_argument) {
        return [$test_argument];
      }, $test_arguments)
    );
  }

}

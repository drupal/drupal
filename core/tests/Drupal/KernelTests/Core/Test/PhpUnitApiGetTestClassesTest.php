<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Test\PhpUnitTestDiscovery;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ::getTestClasses().
 */
#[CoversMethod(PhpUnitTestDiscovery::class, 'getTestClasses')]
#[Group('TestSuites')]
#[Group('Test')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class PhpUnitApiGetTestClassesTest extends KernelTestBase {

  /**
   * Checks PHPUnit API based discovery.
   */
  #[DataProvider('argumentsProvider')]
  public function testSuite(
    array $suites = [],
    ?string $extension = NULL,
    ?string $directory = NULL,
    array $testsArg = [],
    ?int $minCount = NULL,
    ?int $maxCount = NULL,
  ): void {
    $configurationFilePath = $this->container->getParameter('app.root') . \DIRECTORY_SEPARATOR . 'core';
    $phpUnitTestDiscovery = PhpUnitTestDiscovery::instance()->setConfigurationFilePath($configurationFilePath);
    $phpUnitList = $phpUnitTestDiscovery->getTestClasses($extension, $suites, $directory, $testsArg);
    $this->assertNotEmpty($phpUnitList);
    if (isset($minCount)) {
      $this->assertGreaterThanOrEqual($minCount, count($phpUnitList));
    }
    if (isset($maxCount)) {
      $this->assertLessThanOrEqual($maxCount, count($phpUnitList));
    }
  }

  /**
   * Provides test data to ::testSuite.
   */
  public static function argumentsProvider(): \Generator {
    yield 'All tests' => [];
    yield 'Testsuite: functional-javascript' => ['suites' => ['functional-javascript']];
    yield 'Testsuite: functional' => ['suites' => ['functional']];
    yield 'Testsuite: kernel' => ['suites' => ['kernel']];
    yield 'Testsuite: unit' => ['suites' => ['unit']];
    yield 'Testsuite: unit-component' => ['suites' => ['unit-component']];
    yield 'Testsuite: build' => ['suites' => ['build']];
    yield 'Extension: system' => ['extension' => 'system'];
    yield 'Extension: system, testsuite: unit' => [
      'suites' => ['unit'],
      'extension' => 'system',
    ];
    yield 'Extension: system, directory' => [
      'extension' => 'system',
      'directory' => 'core/modules/system/tests/src',
    ];
    yield 'Extension: system, testsuite: unit, directory' => [
      'suites' => ['unit'],
      'extension' => 'system',
      'directory' => 'core/modules/system/tests/src',
    ];
    yield 'directory' => [
      'directory' => 'core/modules/system/tests/src',
      'minCount' => 35,
    ];
    // Adding a group argument to the discovery should return only a subset of
    // the tests discovered in the directory, see test case above.
    yield 'directory, tests arg' => [
      'directory' => 'core/modules/system/tests/src',
      'testsArg' => ['Menu'],
      'maxCount' => 1,
    ];
  }

}

<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Test\PhpUnitTestDiscovery;
use Drupal\Core\Test\TestDiscovery;
use Drupal\KernelTests\KernelTestBase;
use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests ::getTestClasses() between TestDiscovery and PhpPUnitTestDiscovery.
 *
 * PhpPUnitTestDiscovery uses PHPUnit API to build the list of test classes,
 * while TestDiscovery uses Drupal legacy code.
 */
#[CoversClass(PhpUnitTestDiscovery::class)]
#[Group('TestSuites')]
#[Group('Test')]
#[Group('#slow')]
class PhpUnitApiGetTestClassesTest extends KernelTestBase {

  /**
   * Checks that Drupal legacy and PHPUnit API based discoveries are equal.
   */
  #[DataProvider('argumentsProvider')]
  #[IgnoreDeprecations]
  public function testEquality(array $suites, ?string $extension = NULL, ?string $directory = NULL): void {
    // PHPUnit discovery.
    $configurationFilePath = $this->container->getParameter('app.root') . \DIRECTORY_SEPARATOR . 'core';
    // @todo once PHPUnit 10 is no longer used, remove the condition.
    // @see https://www.drupal.org/project/drupal/issues/3497116
    if (RunnerVersion::getMajor() >= 11) {
      $configurationFilePath .= \DIRECTORY_SEPARATOR . '.phpunit-next.xml';
    }
    $phpUnitTestDiscovery = PhpUnitTestDiscovery::instance()->setConfigurationFilePath($configurationFilePath);
    $phpUnitList = $phpUnitTestDiscovery->getTestClasses($extension, $suites, $directory);

    // Legacy TestDiscovery.
    $testDiscovery = new TestDiscovery(
      $this->container->getParameter('app.root'),
      $this->container->get('class_loader')
    );
    $internalList = $testDiscovery->getTestClasses($extension, $suites, $directory);

    // Downgrade results to make them comparable, working around bugs and
    // additions.
    // 1. Remove TestDiscovery empty groups.
    $internalList = array_filter($internalList);
    // 2. Remove TestDiscovery '##no-group-annotations' group.
    unset($internalList['##no-group-annotations']);
    // 3. Remove 'file' and 'tests_count' keys from PHPUnit results.
    foreach ($phpUnitList as &$group) {
      foreach ($group as &$testClass) {
        unset($testClass['file']);
        unset($testClass['tests_count']);
      }
    }
    // 4. Remove from PHPUnit results groups not found by TestDiscovery.
    $phpUnitList = array_intersect_key($phpUnitList, $internalList);
    // 5. Remove from PHPUnit groups classes not found by TestDiscovery.
    foreach ($phpUnitList as $groupName => &$group) {
      $group = array_intersect_key($group, $internalList[$groupName]);
    }
    // 6. Remove from PHPUnit test classes groups not found by TestDiscovery.
    foreach ($phpUnitList as $groupName => &$group) {
      foreach ($group as $testClassName => &$testClass) {
        $testClass['groups'] = array_intersect_key($testClass['groups'], $internalList[$groupName][$testClassName]['groups']);
      }
    }

    $this->assertEquals($internalList, $phpUnitList);
  }

  /**
   * Provides test data to ::testEquality.
   */
  public static function argumentsProvider(): \Generator {
    yield 'All tests' => ['suites' => []];
    yield 'Testsuite: functional-javascript' => ['suites' => ['PHPUnit-FunctionalJavascript']];
    yield 'Testsuite: functional' => ['suites' => ['PHPUnit-Functional']];
    yield 'Testsuite: kernel' => ['suites' => ['PHPUnit-Kernel']];
    yield 'Testsuite: unit' => ['suites' => ['PHPUnit-Unit']];
    yield 'Testsuite: unit-component' => ['suites' => ['PHPUnit-Unit-Component']];
    yield 'Testsuite: build' => ['suites' => ['PHPUnit-Build']];
    yield 'Extension: system' => ['suites' => [], 'extension' => 'system'];
    yield 'Extension: system, testsuite: unit' => [
      'suites' => ['PHPUnit-Unit'],
      'extension' => 'system',
    ];
    yield 'Extension: system, directory' => [
      'suites' => [],
      'extension' => 'system',
      'directory' => 'core/modules/system/tests/src',
    ];
    yield 'Extension: system, testsuite: unit, directory' => [
      'suites' => ['PHPUnit-Unit'],
      'extension' => 'system',
      'directory' => 'core/modules/system/tests/src',
    ];
  }

}

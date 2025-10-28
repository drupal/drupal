<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Test\PhpUnitTestDiscovery;
use Drupal\Core\Test\TestDiscovery;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ::findAllClassFiles() between TestDiscovery and PhpUnitTestDiscovery.
 *
 * PhpUnitTestDiscovery uses PHPUnit API to build the list of test classes,
 * while TestDiscovery uses Drupal legacy code.
 */
#[CoversClass(PhpUnitTestDiscovery::class)]
#[Group('TestSuites')]
#[Group('Test')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class PhpUnitApiFindAllClassFilesTest extends KernelTestBase {

  /**
   * Checks that Drupal legacy and PHPUnit API based discoveries are equal.
   */
  #[DataProvider('argumentsProvider')]
  #[IgnoreDeprecations]
  public function testEquality(?string $extension = NULL, ?string $directory = NULL): void {
    // PHPUnit discovery.
    $configurationFilePath = $this->container->getParameter('app.root') . \DIRECTORY_SEPARATOR . 'core';
    $phpUnitTestDiscovery = PhpUnitTestDiscovery::instance()->setConfigurationFilePath($configurationFilePath);
    $phpUnitList = $phpUnitTestDiscovery->findAllClassFiles($extension, $directory);

    // Legacy TestDiscovery.
    $testDiscovery = new TestDiscovery(
      $this->container->getParameter('app.root'),
      $this->container->get('class_loader')
    );
    $internalList = $testDiscovery->findAllClassFiles($extension, $directory);

    // Downgrade results to make them comparable, working around bugs and
    // additions.
    // 1. TestDiscovery discovers non-test classes that PHPUnit does not.
    $internalList = array_intersect_key($internalList, $phpUnitList);

    $this->assertEquals($internalList, $phpUnitList);
  }

  /**
   * Provides test data to ::testEquality.
   */
  public static function argumentsProvider(): \Generator {
    yield 'All tests' => [];
    yield 'Extension: system' => ['extension' => 'system'];
    yield 'Extension: system, directory' => [
      'extension' => 'system',
      'directory' => 'core/modules/system/tests/src',
    ];
  }

}

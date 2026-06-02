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
 * Tests ::findAllClassFiles().
 */
#[CoversMethod(PhpUnitTestDiscovery::class, 'findAllClassFiles')]
#[Group('TestSuites')]
#[Group('Test')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class PhpUnitApiFindAllClassFilesTest extends KernelTestBase {

  /**
   * Checks PHPUnit API based discovery.
   */
  #[DataProvider('argumentsProvider')]
  public function testAllClasses(?string $extension = NULL, ?string $directory = NULL, array $testsArg = []): void {
    // PHPUnit discovery.
    $configurationFilePath = $this->container->getParameter('app.root') . \DIRECTORY_SEPARATOR . 'core';
    $phpUnitTestDiscovery = PhpUnitTestDiscovery::instance()->setConfigurationFilePath($configurationFilePath);
    $phpUnitList = $phpUnitTestDiscovery->findAllClassFiles($extension, $directory, $testsArg);
    $this->assertNotEmpty($phpUnitList);
  }

  /**
   * Provides test data to ::testAllClasses.
   */
  public static function argumentsProvider(): \Generator {
    yield 'All tests' => [];
    yield 'Extension: system' => ['extension' => 'system'];
    yield 'Extension: system, directory' => [
      'extension' => 'system',
      'directory' => 'core/modules/system/tests/src',
    ];
    yield 'directory, tests arg' => [
      'extension' => NULL,
      'directory' => 'core/modules/system/tests/src',
      'testsArg' => ['Menu'],
    ];
  }

}

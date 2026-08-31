<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Test;

use Drupal\Core\Test\PhpUnitTestDiscovery;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Finder\Finder;

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
  public function testAllClasses(?string $extension = NULL, ?string $directory = NULL, array $testsArg = [], ?string $expectedDirectory = NULL): void {
    $core = $this->container->getParameter('app.root') . '/core';
    $phpUnitTestDiscovery = PhpUnitTestDiscovery::instance()->setConfigurationFilePath($core);
    $phpUnitList = $phpUnitTestDiscovery->findAllClassFiles($extension, $directory, $testsArg);

    $testFiles = [];
    foreach (Finder::create()->files()->name('*Test.php')->in($core . ($expectedDirectory ? '/' . $expectedDirectory : '')) as $file) {
      $path = $file->getPathname();
      if ($expectedDirectory || str_starts_with($path, $core . '/tests/Drupal/') || str_contains($path, '/tests/src/')) {
        $testFiles[] = $path;
      }
    }

    $this->assertSame([], array_values(array_diff($testFiles, $phpUnitList)));
  }

  /**
   * Provides test data to ::testAllClasses().
   */
  public static function argumentsProvider(): \Generator {
    yield 'All tests' => [];
    yield 'System kernel Menu tests' => [
      'extension' => 'system',
      'directory' => 'core/modules/system/tests/src/Kernel',
      'testsArg' => ['Menu'],
      'expectedDirectory' => 'modules/system/tests/src/Kernel/Menu',
    ];
  }

}

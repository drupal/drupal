<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Test;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Process\Process;

/**
 * Test to ensure that functional tests produce debug HTML output when required.
 *
 * @group browsertestbase
 */
class FunctionalTestDebugHtmlOutputTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that functional tests produce debug HTML output when required.
   *
   * Note: this test must be a BrowserTestBase to ensure all requirements for
   * running a functional test are met.
   */
  public function testFunctionalTestDebugHtmlOutput(): void {
    $command = [
      'vendor/bin/phpunit',
      '--configuration',
      'core',
      'core/tests/Drupal/FunctionalTests/Test/FunctionalTestDebugHtmlOutputHelperTest.php',
    ];

    // Test with the default output directory, specified by BROWSERTEST_OUTPUT_DIRECTORY.
    $process = new Process($command);
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run();
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertMatchesRegularExpression('/HTML output was generated, \d+ page\(s\)\./m', $process->getOutput());

    // Test with verbose output.
    $process = new Process($command);
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run(NULL, [
      'BROWSERTEST_OUTPUT_VERBOSE' => '1',
    ]);
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output was generated.', $process->getOutput());
    $this->assertStringContainsString('Drupal_FunctionalTests_Test_FunctionalTestDebugHtmlOutputHelperTest', $process->getOutput());

    // Test with a wrong output directory.
    $process = new Process($command);
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run(NULL, [
      'BROWSERTEST_OUTPUT_DIRECTORY' => 'can_we_assume_that_a_subdirectory_with_this_name_does_not_exist',
    ]);
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output directory can_we_assume_that_a_subdirectory_with_this_name_does_not_exist is not a writable directory.', $process->getOutput());
  }

}

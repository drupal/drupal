<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Process\Process;

/**
 * @group TestSuites
 * @group Test
 */
class PhpUnitCliTest extends UnitTestCase {

  /**
   * Ensure that the test suites are able to discover tests without incident.
   */
  public function testPhpUnitListTests() {
    // Generate the list of tests for all the tests the suites can discover.
    // The goal here is to successfully generate the list, without any
    // duplicate namespace errors or so forth. This keeps us from committing
    // tests which don't break under run-tests.sh, but do break under the
    // phpunit test runner tool.
    $process = Process::fromShellCommandline('vendor/bin/phpunit --configuration core --verbose --list-tests');
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run();
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n"
    );
  }

  /**
   * Ensures that functional tests produce debug HTML output when required.
   */
  public function testFunctionalTestDebugHtmlOutput() {
    if (!getenv('BROWSERTEST_OUTPUT_DIRECTORY')) {
      $this->markTestSkipped('This test requires the environment variable BROWSERTEST_OUTPUT_DIRECTORY to be set.');
    }

    // Test with the specified output directory.
    $process = Process::fromShellCommandline('vendor/bin/phpunit --configuration core --verbose core/modules/image/tests/src/Functional/ImageDimensionsTest.php');
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run();
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output was generated', $process->getOutput());
    $this->assertStringContainsString('Drupal_Tests_image_Functional_ImageDimensionsTest', $process->getOutput());

    // Test with a wrong output directory.
    $process = Process::fromShellCommandline('vendor/bin/phpunit --configuration core --verbose core/modules/image/tests/src/Functional/ImageDimensionsTest.php');
    $process->setWorkingDirectory($this->root)
      ->setTimeout(300)
      ->setIdleTimeout(300);
    $process->run(NULL, ['BROWSERTEST_OUTPUT_DIRECTORY' => 'can_we_assume_that_a_subdirectory_with_this_name_does_not_exist']);
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n");
    $this->assertStringContainsString('HTML output directory can_we_assume_that_a_subdirectory_with_this_name_does_not_exist is not a writable directory.', $process->getOutput());
  }

}

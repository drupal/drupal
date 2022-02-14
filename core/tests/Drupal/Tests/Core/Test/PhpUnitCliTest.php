<?php

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
    if (getenv('BROWSERTEST_OUTPUT_DIRECTORY') === FALSE) {
      $this->markTestSkipped('This test requires the environment variable BROWSERTEST_OUTPUT_DIRECTORY to be set.');
    }
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
    $this->assertStringContainsString('Drupal_Tests_image_Functional_ImageDimensionsTest-1', $process->getOutput());
  }

}

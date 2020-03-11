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
    $process = new Process('vendor/bin/phpunit --configuration core --verbose --list-tests');
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

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;
use Symfony\Component\Process\Process;

/**
 * @group TestSuites
 * @group Test
 */
class PhpUnitCliTest extends UnitTestCase {

  /**
   * Ensure that the test suites are able to discover tests without incident.
   *
   * Generate the list of tests for all the tests that PHPUnit can discover.
   * The goal here is to successfully generate the list, without any
   * duplicate namespace errors, deprecation errors or so forth. This keeps
   * us from committing tests which don't break under run-tests.sh, but do
   * break under the PHPUnit CLI test runner tool.
   */
  public function testPhpUnitListTests(): void {
    $command = [
      'vendor/bin/phpunit',
      '--configuration',
      'core',
      '--list-tests',
    ];

    // PHPUnit 10 dropped the --verbose command line option.
    if (RunnerVersion::getMajor() < 10) {
      $command[] = '--verbose';
    }

    $process = new Process($command, $this->root);
    $process
      ->setTimeout(300)
      ->setIdleTimeout(300)
      ->run();
    $this->assertEquals(0, $process->getExitCode(),
      'COMMAND: ' . $process->getCommandLine() . "\n" .
      'OUTPUT: ' . $process->getOutput() . "\n" .
      'ERROR: ' . $process->getErrorOutput() . "\n"
    );
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Plugin;

use Symfony\Component\Process\Process;

/**
 * Convenience class for creating fixtures.
 */
trait ExecTrait {

  /**
   * Runs an arbitrary command.
   *
   * @param string $cmd
   *   The command to execute (escaped as required)
   * @param string $cwd
   *   The current working directory to run the command from.
   * @param array $env
   *   Environment variables to define for the subprocess.
   * @param string $error_output
   *   (optional) Passed by reference to allow error output to be tested.
   *
   * @return string
   *   Standard output from the command
   */
  protected function mustExec($cmd, $cwd, array $env = [], string &$error_output = ''): string {
    $process = Process::fromShellCommandline($cmd, $cwd, $env + ['PATH' => getenv('PATH'), 'HOME' => getenv('HOME')]);
    $process->setTimeout(300)->setIdleTimeout(300)->run();
    $exitCode = $process->getExitCode();
    if (0 != $exitCode) {
      throw new \RuntimeException("Exit code: {$exitCode}\n\n" . $process->getErrorOutput() . "\n\n" . $process->getOutput());
    }
    $error_output = $process->getErrorOutput();
    return $process->getOutput();
  }

}

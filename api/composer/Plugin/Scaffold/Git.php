<?php

namespace Drupal\Composer\Plugin\Scaffold;

use Composer\IO\IOInterface;
use Composer\Util\ProcessExecutor;

/**
 * Provide some Git utility operations.
 *
 * @internal
 */
class Git {

  /**
   * This class provides only static methods.
   */
  private function __construct() {
  }

  /**
   * Determines whether the specified scaffold file is already ignored.
   *
   * @param \Composer\IO\IOInterface $io
   *   The Composer IO interface.
   * @param string $path
   *   Path to scaffold file to check.
   * @param string $dir
   *   Base directory for git process.
   *
   * @return bool
   *   Whether the specified file is already ignored or not (TRUE if ignored).
   */
  public static function checkIgnore(IOInterface $io, $path, $dir = NULL) {
    $process = new ProcessExecutor($io);
    $output = '';
    $exitCode = $process->execute('git check-ignore ' . $process->escape($path), $output, $dir);
    return $exitCode == 0;
  }

  /**
   * Determines whether the specified scaffold file is tracked by git.
   *
   * @param \Composer\IO\IOInterface $io
   *   The Composer IO interface.
   * @param string $path
   *   Path to scaffold file to check.
   * @param string $dir
   *   Base directory for git process.
   *
   * @return bool
   *   Whether the specified file is already tracked or not (TRUE if tracked).
   */
  public static function checkTracked(IOInterface $io, $path, $dir = NULL) {
    $process = new ProcessExecutor($io);
    $output = '';
    $exitCode = $process->execute('git ls-files --error-unmatch ' . $process->escape($path), $output, $dir);
    return $exitCode == 0;
  }

  /**
   * Checks to see if the project root dir is in a git repository.
   *
   * @param \Composer\IO\IOInterface $io
   *   The Composer IO interface.
   * @param string $dir
   *   Base directory for git process.
   *
   * @return bool
   *   True if this is a repository.
   */
  public static function isRepository(IOInterface $io, $dir = NULL) {
    $process = new ProcessExecutor($io);
    $output = '';
    $exitCode = $process->execute('git rev-parse --show-toplevel', $output, $dir);
    return $exitCode == 0;
  }

}

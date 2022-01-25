<?php

namespace Drupal\Core\Security;

use TYPO3\PharStreamWrapper\Assertable;
use TYPO3\PharStreamWrapper\Helper;
use TYPO3\PharStreamWrapper\Exception;

/**
 * An alternate PharExtensionInterceptor to support phar-based CLI tools.
 *
 * @internal
 *
 * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. No replacement
 *   is provided.
 *
 * @see https://www.drupal.org/project/drupal/issues/3252439
 * @see \TYPO3\PharStreamWrapper\Interceptor\PharExtensionInterceptor
 */
class PharExtensionInterceptor implements Assertable {

  /**
   * Determines whether phar file is allowed to execute.
   *
   * The phar file is allowed to execute if:
   * - the base file name has a ".phar" suffix.
   * - it is the CLI tool that has invoked the interceptor.
   *
   * @param string $path
   *   The path of the phar file to check.
   * @param string $command
   *   The command being carried out.
   *
   * @return bool
   *   TRUE if the phar file is allowed to execute.
   *
   * @throws \TYPO3\PharStreamWrapper\Exception
   *   Thrown when the file is not allowed to execute.
   */
  public function assert(string $path, string $command): bool {
    if ($this->baseFileContainsPharExtension($path)) {
      return TRUE;
    }
    throw new Exception(
      sprintf(
        'Unexpected file extension in "%s"',
        $path
      ),
      1535198703
    );
  }

  /**
   * Determines if a path has a .phar extension or invoked execution.
   *
   * @param string $path
   *   The path of the phar file to check.
   *
   * @return bool
   *   TRUE if the file has a .phar extension or if the execution has been
   *   invoked by the phar file.
   */
  private function baseFileContainsPharExtension($path) {
    $baseFile = Helper::determineBaseFile($path);
    if ($baseFile === NULL) {
      return FALSE;
    }
    // If the stream wrapper is registered by invoking a phar file that does
    // not have .phar extension then this should be allowed. For example, some
    // CLI tools recommend removing the extension.
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    // Find the last entry in the backtrace containing a 'file' key as
    // sometimes the last caller is executed outside the scope of a file. For
    // example, this occurs with shutdown functions.
    do {
      $caller = array_pop($backtrace);
    } while (empty($caller['file']) && !empty($backtrace));
    if (isset($caller['file']) && $baseFile === Helper::determineBaseFile($caller['file'])) {
      return TRUE;
    }
    $fileExtension = pathinfo($baseFile, PATHINFO_EXTENSION);
    return strtolower($fileExtension) === 'phar';
  }

}

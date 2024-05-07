<?php

declare(strict_types=1);

namespace Drupal\TestTools\ErrorHandler;

use Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler;
use PHPUnit\Event\Code\NoTestCaseObjectOnCallStackException;
use PHPUnit\Runner\ErrorHandler as PhpUnitErrorHandler;

/**
 * Drupal's PHPUnit base error handler.
 *
 * This code works in coordination with DeprecationHandler.
 *
 * This error handler is registered during PHPUnit's runner bootstrap, and is
 * essentially used to capture deprecations occurring before tests are run (for
 * example, deprecations triggered by the DebugClassloader). When test runs
 * are prepared, a test specific TestErrorHandler is activated instead.
 *
 * @see \Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler
 *
 * @internal
 */
final class BootstrapErrorHandler {

  /**
   * @param \PHPUnit\Runner\ErrorHandler $phpUnitErrorHandler
   *   An instance of PHPUnit's runner own error handler. Any error not
   *   managed here will fall back to it.
   */
  public function __construct(
    private readonly PhpUnitErrorHandler $phpUnitErrorHandler,
  ) {
  }

  /**
   * Executes when the object is called as a function.
   *
   * @param int $errorNumber
   *   The level of the error raised.
   * @param string $errorString
   *   The error message.
   * @param string $errorFile
   *   The filename that the error was raised in.
   * @param int $errorLine
   *   The line number the error was raised at.
   *
   * @return bool
   *   TRUE to stop error handling, FALSE to let the normal error handler
   *   continue.
   */
  public function __invoke(int $errorNumber, string $errorString, string $errorFile, int $errorLine): bool {
    if (!DeprecationHandler::isEnabled()) {
      throw new \RuntimeException(__METHOD__ . '() must not be called if the deprecation handler is not enabled.');
    }

    // We collect a deprecation no matter what.
    if (E_USER_DEPRECATED === $errorNumber || E_DEPRECATED === $errorNumber) {
      $prefix = (error_reporting() & $errorNumber) ? 'Unsilenced deprecation: ' : '';
      DeprecationHandler::collectActualDeprecation($prefix . $errorString);
    }

    // If the deprecation handled is one of those in the ignore list, we keep
    // running.
    if ((E_USER_DEPRECATED === $errorNumber || E_DEPRECATED === $errorNumber) && DeprecationHandler::isIgnoredDeprecation($errorString)) {
      return TRUE;
    }

    // In all other cases (errors, warnings, deprecations to be reported), we
    // fall back to PHPUnit's error handler, an instance of which was created
    // when this error handler was created.
    try {
      call_user_func($this->phpUnitErrorHandler, $errorNumber, $errorString, $errorFile, $errorLine);
    }
    catch (NoTestCaseObjectOnCallStackException $e) {
      // If we end up here, it's likely because a test's processing has
      // finished already and we are processing an error that occurred while
      // dealing with STDOUT rewinding or truncating. Do nothing.
    }
    return TRUE;
  }

}

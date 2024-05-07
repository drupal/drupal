<?php

declare(strict_types=1);

namespace Drupal\TestTools\ErrorHandler;

use Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler;
use PHPUnit\Framework\TestCase;

/**
 * Drupal's PHPUnit test level error handler.
 *
 * This code works in coordination with DeprecationHandler.
 *
 * This error handler is registered during the preparation of a PHPUnit's test,
 * and is essentially used to capture deprecations occurring during test
 * executions. When test runs are torn down, the more generic
 * BootstrapErrorHandler is restored.
 *
 * @see \Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler
 *
 * @internal
 */
final class TestErrorHandler {

  /**
   * @param \Drupal\TestTools\ErrorHandler\BootstrapErrorHandler $parentHandler
   *   The parent error handler.
   * @param \PHPUnit\Framework\TestCase $testCase
   *   The test case being executed.
   */
  public function __construct(
    private readonly BootstrapErrorHandler $parentHandler,
    private readonly TestCase $testCase,
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

    // We are within a test execution. If we have a deprecation and the test is
    // a deprecation test, than we just collect the deprecation and return to
    // execution, since deprecations are expected.
    if ((E_USER_DEPRECATED === $errorNumber || E_DEPRECATED === $errorNumber) && DeprecationHandler::isDeprecationTest($this->testCase)) {
      $prefix = (error_reporting() & $errorNumber) ? 'Unsilenced deprecation: ' : '';
      DeprecationHandler::collectActualDeprecation($prefix . $errorString);
      return TRUE;
    }

    // In all other cases (errors, warnings, deprecations in normal tests), we
    // fall back to the parent error handler, which is the one that was
    // registered in the test runner bootstrap (BootstrapErrorHandler).
    call_user_func($this->parentHandler, $errorNumber, $errorString, $errorFile, $errorLine);
    return TRUE;
  }

}

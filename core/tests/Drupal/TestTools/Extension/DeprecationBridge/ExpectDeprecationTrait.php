<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\DeprecationBridge;

use Drupal\Core\Utility\Error;
use Drupal\TestTools\ErrorHandler\TestErrorHandler;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

/**
 * A trait to include in Drupal tests to manage expected deprecations.
 *
 * This code works in coordination with DeprecationHandler.
 *
 * This trait is a replacement for symfony/phpunit-bridge that is not
 * supporting PHPUnit 10. In the future this extension might be dropped if
 * PHPUnit will support all deprecation management needs.
 *
 * @see \Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler
 *
 * @internal
 */
trait ExpectDeprecationTrait {

  /**
   * Sets up the test error handler.
   *
   * This method is run before each test's ::setUp() method, and when the
   * DeprecationHandler is active, resets the extension to be able to collect
   * the test's deprecations, and sets TestErrorHandler as the current error
   * handler.
   *
   * @see \Drupal\TestTools\ErrorHandler\TestErrorHandler
   */
  #[Before]
  public function setUpErrorHandler(): void {
    if (!DeprecationHandler::isEnabled()) {
      return;
    }

    DeprecationHandler::reset();
    set_error_handler(new TestErrorHandler(Error::currentErrorHandler(), $this));
  }

  /**
   * Tears down the test error handler.
   *
   * This method is run after each test's ::tearDown() method, and checks if
   * collected deprecations match the expectations; it also resets the error
   * handler to the one set prior of the change made by ::setUpErrorHandler().
   */
  #[After]
  public function tearDownErrorHandler(): void {
    if (!DeprecationHandler::isEnabled()) {
      return;
    }

    // We expect that the current error handler is the one set by
    // ::setUpErrorHandler() prior to the start of the test execution. If not,
    // the error handler was changed during the test execution but not properly
    // restored during ::tearDown().
    $handler = Error::currentErrorHandler();
    if (!$handler instanceof TestErrorHandler) {
      throw new \RuntimeException(sprintf('%s registered its own error handler without restoring the previous one before or during tear down. This can cause unpredictable test results. Ensure the test cleans up after itself.', $this->name()));
    }
    restore_error_handler();

    // Checks if collected deprecations match the expectations.
    if (DeprecationHandler::getExpectedDeprecations()) {
      $prefix = "@expectedDeprecation:\n";
      $expDep = $prefix . '%A  ' . implode("\n%A  ", DeprecationHandler::getExpectedDeprecations()) . "\n%A";
      $actDep = $prefix . '  ' . implode("\n  ", DeprecationHandler::getCollectedDeprecations()) . "\n";
      $this->assertStringMatchesFormat($expDep, $actDep);
    }
  }

  /**
   * Adds an expected deprecation.
   *
   * @param string $message
   *   The expected deprecation message.
   */
  public function expectDeprecation(string $message): void {
    if (!DeprecationHandler::isDeprecationTest($this)) {
      throw new \RuntimeException('expectDeprecation() can only be called from tests marked with #[IgnoreDeprecations] or \'@group legacy\'');
    }

    if (!DeprecationHandler::isEnabled()) {
      return;
    }

    DeprecationHandler::expectDeprecation($message);
  }

}

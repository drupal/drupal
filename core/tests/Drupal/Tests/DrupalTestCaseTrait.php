<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\ErrorHandler\BootstrapErrorHandler;
use Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler;
use PHPUnit\Framework\Attributes\After;

/**
 * Provides methods common across all Drupal abstract base test classes.
 *
 * This trait is meant to be used only by test classes.
 */
trait DrupalTestCaseTrait {

  /**
   * Checks the test error handler after test execution.
   */
  #[After]
  public function checkErrorHandlerOnTearDown(): void {
    // We expect that the current error handler is the one set during the
    // PHPUnit bootstrap. If not, the error handler was changed during the test
    // execution but not properly restored during ::tearDown().
    if (DeprecationHandler::isEnabled() && !get_error_handler() instanceof BootstrapErrorHandler) {
      throw new \RuntimeException(sprintf('%s registered its own error handler without restoring the previous one before or during tear down. This can cause unpredictable test results. Ensure the test cleans up after itself.', $this->name()));
    }
  }

}

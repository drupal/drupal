<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\ErrorHandler\BootstrapErrorHandler;
use Drupal\TestTools\Extension\DeprecationBridge\Configuration as DeprecationHandlerConfiguration;
use Drupal\TestTools\Extension\Dump\DebugDump;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Provides methods common across Drupal test classes.
 *
 * This trait is imported in the DrupalTestCase class, that is the common
 * ancestor class for unit, kernel, functional, functional javascript and
 * build base test classes.
 *
 * Normally, you do not need to import this trait explicitly.
 *
 * The trait may need to be imported only in tests classes that directly extend
 * \PHPUnit\Framework\TestCase, like for example Component unit tests, that
 * need to be executed with the Drupal testing framework (typically, using
 * GitLabCI).
 *
 * @see \Drupal\Tests\DrupalTestCase
 * @see \Drupal\Tests\UnitTestCase
 * @see \Drupal\KernelTests\KernelTestBase
 * @see \Drupal\Tests\BrowserTestBase
 * @see \Drupal\BuildTests\Framework\BuildTestBase
 */
trait DrupalTestCaseTrait {

  /**
   * Registers the dumper CLI handler when the DebugDump extension is enabled.
   */
  #[BeforeClass]
  public static function setDebugDumpHandler(): void {
    if (DebugDump::isEnabled()) {
      VarDumper::setHandler(DebugDump::class . '::cliHandler');
    }
  }

  /**
   * Checks legacy SYMFONY_DEPRECATIONS_HELPER env variable is not used.
   */
  #[Before]
  public function checkLegacySymfonyDeprecationHelperEnvVariable(): void {
    if (getenv('SYMFONY_DEPRECATIONS_HELPER') !== FALSE) {
      @trigger_error("Using the SYMFONY_DEPRECATIONS_HELPER environment variable to configure test runs is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. See https://www.drupal.org/node/3594014", E_USER_DEPRECATED);
    }
  }

  /**
   * Checks the test error handler after test execution.
   */
  #[After]
  public function checkErrorHandlerOnTearDown(): void {
    // We expect that the current error handler is the one set during the
    // PHPUnit bootstrap. If not, the error handler was changed during the test
    // execution but not properly restored during ::tearDown().
    if (DeprecationHandlerConfiguration::instance()->projectIgnoresEnabled && !get_error_handler() instanceof BootstrapErrorHandler) {
      throw new \RuntimeException(sprintf('%s registered its own error handler without restoring the previous one before or during tear down. This can cause unpredictable test results. Ensure the test cleans up after itself.', $this->name()));
    }
  }

  /**
   * Expects an exactly matching exception message.
   *
   * Forward compatibility for PHPUnit 13.
   *
   * @param string $message
   *   The expected exception message.
   */
  protected function expectExceptionMessageIs(string $message): void {
    $this->expectExceptionMessage($message);
  }

  /**
   * Expects an exception message containing a specified string.
   *
   * Forward compatibility for PHPUnit 13.
   *
   * @param string $message
   *   The expected exception message.
   */
  protected function expectExceptionMessageIsOrContains(string $message): void {
    $this->expectExceptionMessage($message);
  }

}

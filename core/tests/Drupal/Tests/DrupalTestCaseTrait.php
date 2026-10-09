<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\ErrorHandler\BootstrapErrorHandler;
use Drupal\TestTools\Extension\DeprecationBridge\Configuration as DeprecationHandlerConfiguration;
use Drupal\TestTools\Extension\Dump\DebugDump;
use Drupal\TestTools\PhpUnitCompatibility\ForwardCompatibilityTrait;
use PHPUnit\Framework\Attributes\After;
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

  use ForwardCompatibilityTrait;

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

}

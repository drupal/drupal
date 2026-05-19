<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\ErrorHandler\BootstrapErrorHandler;
use Drupal\TestTools\Extension\DeprecationBridge\DeprecationHandler;
use Drupal\TestTools\Extension\Dump\DebugDump;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Provides methods common across all Drupal abstract base test classes.
 *
 * This trait is meant to be used only by test classes.
 */
trait DrupalTestCaseTrait {

  /**
   * The Drupal root directory.
   */
  protected string $root;

  /**
   * Ensure that the $root property is set initially.
   *
   * This is run with a high priority since other test setup code that runs in
   * #[Before] hooks or setUp() requires access to $root.
   *
   * @internal
   */
  #[Before(100)]
  final protected function setUpRoot(): void {
    if (isset($this->root)) {
      throw new \LogicException("setUpRoot should be called exactly once by PHPUnit's Before test hook and root overrides should happen after.");
    }
    $this->root = dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
  }

  /**
   * Returns the Drupal root directory.
   *
   * @return string
   *   The Drupal root directory.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Access
   *   $this->root directly.
   *
   * @see https://www.drupal.org/node/3574112
   */
  protected static function getDrupalRoot(): string {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Access $this->root directly. See https://www.drupal.org/node/3574112', E_USER_DEPRECATED);
    return dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
  }

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
    if (DeprecationHandler::isEnabled() && !get_error_handler() instanceof BootstrapErrorHandler) {
      throw new \RuntimeException(sprintf('%s registered its own error handler without restoring the previous one before or during tear down. This can cause unpredictable test results. Ensure the test cleans up after itself.', $this->name()));
    }
  }

}

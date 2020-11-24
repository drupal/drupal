<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

use Drupal\auto_updates_test\ReadinessChecker\TestChecker;

/**
 * Common functions for using TestChecker class in tests.
 *
 * @see \Drupal\auto_updates_test\ReadinessChecker\TestChecker
 */
trait TestCheckerTrait {

  /**
   * Sets messages for the test readiness checker.
   *
   * @param string[] $errors
   *   The error messages.
   * @param string[] $warnings
   *   The warning messages.
   *
   * @see \Drupal\auto_updates_test\ReadinessChecker\TestChecker::getMessages()
   */
  protected function setTestMessages(array $errors = [], array $warnings = []): void {
    $this->container->get('state')->set(
      TestChecker::STATE_KEY,
      [
        'errors' => $errors,
        'warnings' => $warnings,
      ]
    );
  }

}

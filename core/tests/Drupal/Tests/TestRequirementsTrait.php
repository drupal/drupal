<?php

declare(strict_types=1);

namespace Drupal\Tests;

/**
 * Allows test classes to require Drupal modules as dependencies.
 *
 * This trait is assumed to be on a subclass of \PHPUnit\Framework\TestCase, and
 * overrides \PHPUnit\Framework\TestCase::checkRequirements(). This allows the
 * test to be marked as skipped before any kernel boot processes have happened.
 */
trait TestRequirementsTrait {

  /**
   * Returns the Drupal root directory.
   *
   * @return string
   *   The Drupal root directory.
   */
  protected static function getDrupalRoot(): string {
    return dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__)), 2);
  }

}

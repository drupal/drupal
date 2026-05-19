<?php

declare(strict_types=1);

namespace Drupal\Tests;

@trigger_error('\Drupal\Tests\TestRequirementsTrait is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal\Tests\DrupalTestCaseTrait. See https://www.drupal.org/node/3574112', E_USER_DEPRECATED);

/**
 * Allows test classes to require Drupal modules as dependencies.
 *
 * This trait is assumed to be on a subclass of \PHPUnit\Framework\TestCase, and
 * overrides \PHPUnit\Framework\TestCase::checkRequirements(). This allows the
 * test to be marked as skipped before any kernel boot processes have happened.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 *   \Drupal\Tests\DrupalTestCaseTrait.
 *
 * @see https://www.drupal.org/node/3574112
 */
trait TestRequirementsTrait {

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

}

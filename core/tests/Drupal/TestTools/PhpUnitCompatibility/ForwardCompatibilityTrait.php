<?php

declare(strict_types=1);

namespace Drupal\TestTools\PhpUnitCompatibility;

/**
 * Provides forward compatibility shim to future PHPUnit versions.
 *
 * This trait is meant to be used only by test classes.
 *
 * @internal
 */
trait ForwardCompatibilityTrait {

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

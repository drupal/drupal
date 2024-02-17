<?php

declare(strict_types=1);

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit9;

use PHPUnit\Util\Test;

/**
 * Drupal's forward compatibility layer with multiple versions of PHPUnit.
 */
trait TestCompatibilityTrait {

  /**
   * Get test name.
   */
  public function name(): string {
    return $this->getName();
  }

  /**
   * Gets @covers defined on the test class.
   *
   * @return string[]
   *   An array of classes listed with the @covers annotation.
   */
  public function getTestClassCovers(): array {
    $annotations = Test::parseTestMethodAnnotations(static::class, $this->name());
    return $annotations['class']['covers'] ?? [];
  }

}

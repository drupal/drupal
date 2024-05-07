<?php

declare(strict_types=1);

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit10;

/**
 * Drupal's forward compatibility layer with multiple versions of PHPUnit.
 *
 * @internal
 */
trait TestCompatibilityTrait {

  /**
   * Gets @covers defined on the test class.
   *
   * @return string[]
   *   An array of classes listed with the @covers annotation.
   */
  public function getTestClassCovers(): array {
    $ret = [];
    foreach ($this->valueObjectForEvents()->metadata()->isCovers()->isClassLevel() as $metadata) {
      $ret[] = $metadata->target();
    }
    return $ret;
  }

}

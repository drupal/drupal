<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;

// In order to manage different method signatures between PHPUnit versions, we
// dynamically load a compatibility trait dependent on the PHPUnit runner
// version.
class_alias("Drupal\TestTools\PhpUnitCompatibility\PhpUnit" . RunnerVersion::getMajor() . "\TestCompatibilityTrait", 'Drupal\Tests\PhpUnitCompatibilityTrait');

// Allow static analysis to find a trait.
if (FALSE) {

  /**
   * Makes Drupal's test API forward compatible with multiple versions of PHPUnit.
   */
  trait PhpUnitCompatibilityTrait {
  }

}

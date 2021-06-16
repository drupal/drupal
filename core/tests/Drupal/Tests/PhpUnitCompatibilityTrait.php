<?php

namespace Drupal\Tests;

use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;

// In order to manage different method signatures between PHPUnit versions, we
// dynamically load a compatibility trait dependent on the PHPUnit runner
// version.
if (!trait_exists(PhpUnitVersionDependentTestCompatibilityTrait::class, FALSE)) {
  class_alias("Drupal\TestTools\PhpUnitCompatibility\PhpUnit" . RunnerVersion::getMajor() . "\TestCompatibilityTrait", PhpUnitVersionDependentTestCompatibilityTrait::class);
}

/**
 * Makes Drupal's test API forward compatible with multiple versions of PHPUnit.
 */
trait PhpUnitCompatibilityTrait {

  use PhpUnitVersionDependentTestCompatibilityTrait;

}

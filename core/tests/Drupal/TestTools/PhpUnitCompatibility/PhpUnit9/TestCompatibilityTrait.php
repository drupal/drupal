<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit9;

use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Drupal's forward compatibility layer with multiple versions of PHPUnit.
 */
trait TestCompatibilityTrait {

  use ProphecyTrait;

}

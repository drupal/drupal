<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit9;

use Prophecy\PhpUnit\ProphecyTrait;

// @todo Replace with a proper dependency when we stop supporting PHPUnit 8.
if (!trait_exists(ProphecyTrait::class)) {
  print "Drupal requires Prophecy PhpUnit when using PHPUnit 9 or greater. Please use 'composer require --dev phpspec/prophecy-phpunit:^2' to ensure that it is present.\n";
  exit(1);
}

/**
 * Drupal's forward compatibility layer with multiple versions of PHPUnit.
 */
trait TestCompatibilityTrait {

  use ProphecyTrait;

}

<?php

namespace Drupal\Tests;

/**
 * A helper class for deprecation of AssertHelperTrait.
 *
 * @todo remove this class in Drupal 10.
 *
 * @internal
 */
class AssertHelperTestClass {
  use AssertHelperTrait;

  public function testMethod($value) {
    return $this->castSafeStrings($value);
  }

}

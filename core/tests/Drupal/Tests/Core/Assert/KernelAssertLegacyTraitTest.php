<?php

namespace Drupal\Tests\Core\Assert;

use Drupal\KernelTests\AssertLegacyTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\KernelTests\AssertLegacyTrait
 * @group Assert
 * @group legacy
 */
class KernelAssertLegacyTraitTest extends UnitTestCase {

  use AssertLegacyTrait;

  /**
   * @expectedDeprecation Support for asserting against non-boolean values in ::assertTrue is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use a different assert method, for example, ::assertNotEmpty(). See https://www.drupal.org/node/3082086
   */
  public function testAssertTrue() {
    $this->assertTrue(1);
  }

  /**
   * @expectedDeprecation Support for asserting against non-boolean values in ::assertFalse is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use a different assert method, for example, ::assertEmpty(). See https://www.drupal.org/node/3082086
   */
  public function testAssertFalse() {
    $this->assertFalse(0);
  }

}

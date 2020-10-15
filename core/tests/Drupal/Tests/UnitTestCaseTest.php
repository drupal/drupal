<?php

namespace Drupal\Tests;

/**
 * Tests for the UnitTestCase class.
 *
 * @group Tests
 */
class UnitTestCaseTest extends UnitTestCase {

  /**
   * Tests deprecation of the ::assertArrayEquals method.
   *
   * @group legacy
   */
  public function testAssertArrayEquals() {
    $this->expectDeprecation('Drupal\Tests\UnitTestCase::assertArrayEquals() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use ::assertEquals(), ::assertEqualsCanonicalizing(), or ::assertSame() instead. See https://www.drupal.org/node/3136304');
    $this->assertArrayEquals([], []);
  }

}

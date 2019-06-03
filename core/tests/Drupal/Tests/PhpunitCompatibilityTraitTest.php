<?php

namespace Drupal\Tests;

/**
 * Tests the PHPUnit forward compatibility trait.
 *
 * @coversDefaultClass \Drupal\Tests\PhpunitCompatibilityTrait
 * @group Tests
 */
class PhpunitCompatibilityTraitTest extends UnitTestCase {

  /**
   * Tests that getMock is available.
   *
   * @covers ::getMock
   * @group legacy
   * @expectedDeprecation \Drupal\Tests\PhpunitCompatibilityTrait::getMock() is deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead. See https://www.drupal.org/node/2907725
   */
  public function testGetMock() {
    $this->assertInstanceOf('\Drupal\Tests\MockTestClassInterface', $this->getMock(MockTestClassInterface::class));
  }

}

interface MockTestClassInterface {

}

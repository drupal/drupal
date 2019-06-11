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

  /**
   * Tests that setExpectedException is available.
   *
   * @covers ::setExpectedException
   * @group legacy
   * @expectedDeprecation \Drupal\Tests\PhpunitCompatibilityTrait:setExpectedException() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Backward compatibility for PHPUnit 4 will no longer be supported. See https://www.drupal.org/node/3056869
   */
  public function testSetExpectedException() {
    $expectedMessage = "Expected message";
    $expectedCode = 100;
    $this->setExpectedException(\Exception::class, $expectedMessage, $expectedCode);
    throw new \Exception($expectedMessage, $expectedCode);
  }

}

interface MockTestClassInterface {

}

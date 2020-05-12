<?php

namespace Drupal\Tests;

use Drupal\Component\Render\FormattableMarkup;

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

  /**
   * Tests that assert*StringContainsString* methods are available.
   *
   * @covers ::assertStringContainsString
   * @covers ::assertStringContainsStringIgnoringCase
   * @covers ::assertStringNotContainsString
   * @covers ::assertStringNotContainsStringIgnoringCase
   */
  public function testAssertStringContainsString() {
    $this->assertStringContainsString("bingo", "foobarbingobongo");
    $this->assertStringContainsStringIgnoringCase("bingo", "foobarBiNgObongo");
    $this->assertStringNotContainsString("buzzer", "BUZZERbingobongo");
    $this->assertStringNotContainsStringIgnoringCase("buzzer", "foobarBiNgObongo");

    // Test with stringable objects.
    $this->assertStringContainsString(new FormattableMarkup("bingo", []), new FormattableMarkup("foobarbingobongo", []));
    $this->assertStringContainsStringIgnoringCase(new FormattableMarkup("bingo", []), new FormattableMarkup("foobarBiNgObongo", []));
    $this->assertStringNotContainsString(new FormattableMarkup("buzzer", []), new FormattableMarkup("BUZZERbingobongo", []));
    $this->assertStringNotContainsStringIgnoringCase(new FormattableMarkup("buzzer", []), new FormattableMarkup("foobarBiNgObongo", []));
  }

  /**
   * Tests that assert(Not)EqualsCanonicalizing methods are available.
   *
   * @covers ::assertEqualsCanonicalizing
   * @covers ::assertNotEqualsCanonicalizing
   */
  public function testAssertEqualsCanonicalizing() {
    $this->assertEqualsCanonicalizing([3, 2, 1], [2, 3, 1]);
    $this->assertNotEqualsCanonicalizing([3, 2, 1], [2, 3, 0, 1]);
  }

  /**
   * Tests that assertIs(Not)* methods are available.
   *
   * @covers ::assertIsArray
   * @covers ::assertIsBool
   * @covers ::assertIsFloat
   * @covers ::assertIsInt
   * @covers ::assertIsNumeric
   * @covers ::assertIsObject
   * @covers ::assertIsResource
   * @covers ::assertIsString
   * @covers ::assertIsScalar
   * @covers ::assertIsCallable
   * @covers ::assertIsNotArray
   * @covers ::assertIsNotBool
   * @covers ::assertIsNotFloat
   * @covers ::assertIsNotInt
   * @covers ::assertIsNotNumeric
   * @covers ::assertIsNotObject
   * @covers ::assertIsNotResource
   * @covers ::assertIsNotString
   * @covers ::assertIsNotScalar
   * @covers ::assertIsNotCallable
   */
  public function testAssertIs() {
    $this->assertIsArray([]);
    $this->assertIsBool(TRUE);
    $this->assertIsFloat(1.0);
    $this->assertIsInt(1);
    $this->assertIsNumeric(1);
    $this->assertIsObject(new class() {});
    $this->assertIsResource(fopen(__FILE__, 'rb'));
    $this->assertIsString('');
    $this->assertIsScalar(1);
    $this->assertIsCallable(function () {});
    $this->assertIsNotArray(1);
    $this->assertIsNotBool([]);
    $this->assertIsNotFloat(1);
    $this->assertIsNotInt(1.0);
    $this->assertIsNotNumeric('');
    $this->assertIsNotObject('');
    $this->assertIsNotResource('');
    $this->assertIsNotString(1);
    $this->assertIsNotScalar([]);
    $this->assertIsNotCallable(1);
  }

}

interface MockTestClassInterface {

}

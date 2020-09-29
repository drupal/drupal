<?php

namespace Drupal\Tests;

/**
 * @coversDefaultClass \Drupal\Tests\Traits\PhpUnitWarnings
 * @group legacy
 */
class PhpUnitWarningsTest extends UnitTestCase {

  /**
   * @expectedDeprecation Test warning for \Drupal\Tests\PhpUnitWarningsTest::testAddWarning()
   */
  public function testAddWarning() {
    $this->addWarning('Test warning for \Drupal\Tests\PhpUnitWarningsTest::testAddWarning()');
  }

  /**
   * @expectedDeprecation Using assertContains() with string haystacks is deprecated and will not be supported in PHPUnit 9. Refactor your test to use assertStringContainsString() or assertStringContainsStringIgnoringCase() instead.
   * @expectedDeprecation The optional $ignoreCase parameter of assertContains() is deprecated and will be removed in PHPUnit 9.
   */
  public function testAssertContains() {
    $this->assertContains('string', 'aaaa_string_aaa');
    $this->assertContains('STRING', 'aaaa_string_aaa', '', TRUE);
  }

  /**
   * @expectedDeprecation Using assertNotContains() with string haystacks is deprecated and will not be supported in PHPUnit 9. Refactor your test to use assertStringNotContainsString() or assertStringNotContainsStringIgnoringCase() instead.
   * @expectedDeprecation The optional $ignoreCase parameter of assertNotContains() is deprecated and will be removed in PHPUnit 9.
   */
  public function testAssertNotContains() {
    $this->assertNotContains('foo', 'bar');
    $this->assertNotContains('FOO', 'bar', '', TRUE);
  }

  /**
   * @expectedDeprecation assertArraySubset() is deprecated and will be removed in PHPUnit 9.
   */
  public function testAssertArraySubset() {
    $this->assertArraySubset(['a'], ['a', 'b']);
  }

  /**
   * @expectedDeprecation assertInternalType() is deprecated and will be removed in PHPUnit 9. Refactor your test to use assertIsArray(), assertIsBool(), assertIsFloat(), assertIsInt(), assertIsNumeric(), assertIsObject(), assertIsResource(), assertIsString(), assertIsScalar(), assertIsCallable(), or assertIsIterable() instead.
   */
  public function testAssertInternalType() {
    $this->assertInternalType('string', 'string');
  }

  /**
   * @expectedDeprecation assertAttributeEquals() is deprecated and will be removed in PHPUnit 9.
   * @expectedDeprecation readAttribute() is deprecated and will be removed in PHPUnit 9.
   * @expectedDeprecation getObjectAttribute() is deprecated and will be removed in PHPUnit 9.
   * @expectedDeprecation assertAttributeSame() is deprecated and will be removed in PHPUnit 9.
   * @expectedDeprecation assertAttributeInstanceOf() is deprecated and will be removed in PHPUnit 9.
   * @expectedDeprecation assertAttributeEmpty() is deprecated and will be removed in PHPUnit 9.
   */
  public function testAssertAttribute() {
    $obj = new class() {
      protected $attribute = 'value';
      protected $class;
      protected $empty;

      public function __construct() {
        $this->class = new \stdClass();
      }

    };
    $this->assertAttributeEquals('value', 'attribute', $obj);
    $this->assertAttributeSame('value', 'attribute', $obj);
    $this->assertAttributeInstanceOf(\stdClass::class, 'class', $obj);
    $this->assertAttributeEmpty('empty', $obj);
  }

  /**
   * @expectedDeprecation The optional $canonicalize parameter of assertEquals() is deprecated and will be removed in PHPUnit 9. Refactor your test to use assertEqualsCanonicalizing() instead.
   */
  public function testAssertEquals() {
    $this->assertEquals(['a', 'b'], ['b', 'a'], '', 0.0, 10, TRUE);
  }

  /**
   * @expectedDeprecation expectExceptionMessageRegExp() is deprecated in PHPUnit 8 and will be removed in PHPUnit 9.
   */
  public function testExpectExceptionMessageRegExp() {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessageRegExp('/An exception .*/');
    throw new \Exception('An exception has been triggered');
  }

}

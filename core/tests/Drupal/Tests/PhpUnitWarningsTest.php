<?php

namespace Drupal\Tests;

use Drupal\TestTools\PhpUnitCompatibility\RunnerVersion;

/**
 * @coversDefaultClass \Drupal\Tests\Traits\PhpUnitWarnings
 * @group legacy
 */
class PhpUnitWarningsTest extends UnitTestCase {

  /**
   * Tests that selected PHPUnit warning is converted to deprecation.
   */
  public function testAddWarning() {
    $this->expectDeprecation('Test warning for \Drupal\Tests\PhpUnitWarningsTest::testAddWarning()');
    $this->addWarning('Test warning for \Drupal\Tests\PhpUnitWarningsTest::testAddWarning()');
  }

  /**
   * Tests assertContains.
   */
  public function testAssertContains() {
    if (RunnerVersion::getMajor() > 8) {
      $this->markTestSkipped("In PHPUnit 9+, argument 2 passed to PHPUnit\Framework\Assert::assertContains() must be iterable.");
    }
    $this->expectDeprecation('Using assertContains() with string haystacks is deprecated and will not be supported in PHPUnit 9. Refactor your test to use assertStringContainsString() or assertStringContainsStringIgnoringCase() instead.');
    $this->expectDeprecation('The optional $ignoreCase parameter of assertContains() is deprecated and will be removed in PHPUnit 9.');
    $this->assertContains('string', 'aaaa_string_aaa');
    $this->assertContains('STRING', 'aaaa_string_aaa', '', TRUE);
  }

  /**
   * Tests assertNotContains.
   */
  public function testAssertNotContains() {
    if (RunnerVersion::getMajor() > 8) {
      $this->markTestSkipped("In PHPUnit 9+, argument 2 passed to PHPUnit\Framework\Assert::assertNotContains() must be iterable.");
    }
    $this->expectDeprecation('Using assertNotContains() with string haystacks is deprecated and will not be supported in PHPUnit 9. Refactor your test to use assertStringNotContainsString() or assertStringNotContainsStringIgnoringCase() instead.');
    $this->expectDeprecation('The optional $ignoreCase parameter of assertNotContains() is deprecated and will be removed in PHPUnit 9.');
    $this->assertNotContains('foo', 'bar');
    $this->assertNotContains('FOO', 'bar', '', TRUE);
  }

  /**
   * Tests assertArraySubset.
   */
  public function testAssertArraySubset() {
    if (RunnerVersion::getMajor() > 8) {
      $this->markTestSkipped("In PHPUnit 9+, assertArraySubset() is removed.");
    }
    $this->expectDeprecation('assertArraySubset() is deprecated and will be removed in PHPUnit 9.');
    $this->assertArraySubset(['a'], ['a', 'b']);
  }

  /**
   * Tests assertInternalType.
   */
  public function testAssertInternalType() {
    if (RunnerVersion::getMajor() > 8) {
      $this->markTestSkipped("In PHPUnit 9+, assertInternalType() is removed.");
    }
    $this->expectDeprecation('assertInternalType() is deprecated and will be removed in PHPUnit 9. Refactor your test to use assertIsArray(), assertIsBool(), assertIsFloat(), assertIsInt(), assertIsNumeric(), assertIsObject(), assertIsResource(), assertIsString(), assertIsScalar(), assertIsCallable(), or assertIsIterable() instead.');
    $this->assertInternalType('string', 'string');
  }

  /**
   * Tests assertion methods accessing class attributes.
   */
  public function testAssertAttribute() {
    if (RunnerVersion::getMajor() > 8) {
      $this->markTestSkipped("In PHPUnit 9+, assertion methods accessing class attributes are removed.");
    }
    $this->expectDeprecation('assertAttributeEquals() is deprecated and will be removed in PHPUnit 9.');
    $this->expectDeprecation('readAttribute() is deprecated and will be removed in PHPUnit 9.');
    $this->expectDeprecation('getObjectAttribute() is deprecated and will be removed in PHPUnit 9.');
    $this->expectDeprecation('assertAttributeSame() is deprecated and will be removed in PHPUnit 9.');
    $this->expectDeprecation('assertAttributeInstanceOf() is deprecated and will be removed in PHPUnit 9.');
    $this->expectDeprecation('assertAttributeEmpty() is deprecated and will be removed in PHPUnit 9.');
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
   * Tests assertEquals.
   */
  public function testAssertEquals() {
    if (RunnerVersion::getMajor() > 8) {
      $this->markTestSkipped("In PHPUnit 9+, the \$canonicalize parameter of assertEquals() is removed.");
    }
    $this->expectDeprecation('The optional $canonicalize parameter of assertEquals() is deprecated and will be removed in PHPUnit 9. Refactor your test to use assertEqualsCanonicalizing() instead.');
    $this->assertEquals(['a', 'b'], ['b', 'a'], '', 0.0, 10, TRUE);
  }

  /**
   * Tests expectExceptionMessageRegExp.
   */
  public function testExpectExceptionMessageRegExp() {
    if (RunnerVersion::getMajor() > 8) {
      $this->markTestSkipped("In PHPUnit 9+, expectExceptionMessageRegExp() is removed.");
    }
    $this->expectDeprecation('expectExceptionMessageRegExp() is deprecated in PHPUnit 8 and will be removed in PHPUnit 9.');
    $this->expectException(\Exception::class);
    $this->expectExceptionMessageRegExp('/An exception .*/');
    throw new \Exception('An exception has been triggered');
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Assertion;

use PHPUnit\Framework\TestCase;
use Drupal\Component\Assertion\Inspector;
use Drupal\TestTools\Extension\DeprecationBridge\ExpectDeprecationTrait;

/**
 * @coversDefaultClass \Drupal\Component\Assertion\Inspector
 * @group Assertion
 */
class InspectorTest extends TestCase {

  use ExpectDeprecationTrait;

  /**
   * Tests asserting all members are strings.
   *
   * @covers ::assertAllStrings
   * @dataProvider providerTestAssertAllStrings
   */
  public function testAssertAllStrings($input, $expected): void {
    $this->assertSame($expected, Inspector::assertAllStrings($input));
  }

  public static function providerTestAssertAllStrings() {
    $data = [
      'empty-array' => [[], TRUE],
      'array-with-strings' => [['foo', 'bar'], TRUE],
      'string' => ['foo', FALSE],
      'array-with-strings-with-colon' => [['foo', 'bar', 'llama:2001988', 'baz', 'llama:14031991'], TRUE],

      'with-FALSE' => [[FALSE], FALSE],
      'with-TRUE' => [[TRUE], FALSE],
      'with-string-and-boolean' => [['foo', FALSE], FALSE],
      'with-NULL' => [[NULL], FALSE],
      'string-with-NULL' => [['foo', NULL], FALSE],
      'integer' => [[1337], FALSE],
      'string-and-integer' => [['foo', 1337], FALSE],
      'double' => [[3.14], FALSE],
      'string-and-double' => [['foo', 3.14], FALSE],
      'array' => [[[]], FALSE],
      'string-and-array' => [['foo', []], FALSE],
      'string-and-nested-array' => [['foo', ['bar']], FALSE],
      'object' => [[new \stdClass()], FALSE],
      'string-and-object' => [['foo', new StringObject()], FALSE],
    ];

    return $data;
  }

  /**
   * Tests asserting all members are strings or objects with __toString().
   *
   * @covers ::assertAllStringable
   */
  public function testAssertAllStringable(): void {
    $this->assertTrue(Inspector::assertAllStringable([]));
    $this->assertTrue(Inspector::assertAllStringable(['foo', 'bar']));
    $this->assertFalse(Inspector::assertAllStringable('foo'));
    $this->assertTrue(Inspector::assertAllStringable(['foo', new StringObject()]));
  }

  /**
   * Tests asserting all members are arrays.
   *
   * @covers ::assertAllArrays
   */
  public function testAssertAllArrays(): void {
    $this->assertTrue(Inspector::assertAllArrays([]));
    $this->assertTrue(Inspector::assertAllArrays([[], []]));
    $this->assertFalse(Inspector::assertAllArrays([[], 'foo']));
  }

  /**
   * Tests asserting array is 0-indexed - the strict definition of array.
   *
   * @covers ::assertStrictArray
   */
  public function testAssertStrictArray(): void {
    $this->assertTrue(Inspector::assertStrictArray([]));
    $this->assertTrue(Inspector::assertStrictArray(['bar', 'foo']));
    $this->assertFalse(Inspector::assertStrictArray(['foo' => 'bar', 'bar' => 'foo']));
  }

  /**
   * Tests asserting all members are strict arrays.
   *
   * @covers ::assertAllStrictArrays
   */
  public function testAssertAllStrictArrays(): void {
    $this->assertTrue(Inspector::assertAllStrictArrays([]));
    $this->assertTrue(Inspector::assertAllStrictArrays([[], []]));
    $this->assertFalse(Inspector::assertAllStrictArrays([['foo' => 'bar', 'bar' => 'foo']]));
  }

  /**
   * Tests asserting all members have specified keys.
   *
   * @covers ::assertAllHaveKey
   */
  public function testAssertAllHaveKey(): void {
    $this->assertTrue(Inspector::assertAllHaveKey([]));
    $this->assertTrue(Inspector::assertAllHaveKey([['foo' => 'bar', 'bar' => 'foo']]));
    $this->assertTrue(Inspector::assertAllHaveKey([['foo' => 'bar', 'bar' => 'foo']], 'foo'));
    $this->assertTrue(Inspector::assertAllHaveKey([['foo' => 'bar', 'bar' => 'foo']], 'bar', 'foo'));
    $this->assertFalse(Inspector::assertAllHaveKey([['foo' => 'bar', 'bar' => 'foo']], 'bar', 'foo', 'moo'));
  }

  /**
   * Tests asserting all members are integers.
   *
   * @covers ::assertAllIntegers
   */
  public function testAssertAllIntegers(): void {
    $this->assertTrue(Inspector::assertAllIntegers([]));
    $this->assertTrue(Inspector::assertAllIntegers([1, 2, 3]));
    $this->assertFalse(Inspector::assertAllIntegers([1, 2, 3.14]));
    $this->assertFalse(Inspector::assertAllIntegers([1, '2', 3]));
  }

  /**
   * Tests asserting all members are floating point variables.
   *
   * @covers ::assertAllFloat
   */
  public function testAssertAllFloat(): void {
    $this->assertTrue(Inspector::assertAllFloat([]));
    $this->assertTrue(Inspector::assertAllFloat([1.0, 2.1, 3.14]));
    $this->assertFalse(Inspector::assertAllFloat([1, 2.1, 3.14]));
    $this->assertFalse(Inspector::assertAllFloat([1.0, '2', 3]));
    $this->assertFalse(Inspector::assertAllFloat(['Titanic']));
  }

  /**
   * Tests asserting all members are callable.
   *
   * @covers ::assertAllCallable
   */
  public function testAllCallable(): void {
    $this->assertTrue(Inspector::assertAllCallable([
      'strchr',
      [$this, 'callMe'],
      [__CLASS__, 'callMeStatic'],
      function () {
        return TRUE;
      },
    ]));

    $this->assertFalse(Inspector::assertAllCallable([
      'strchr',
      [$this, 'callMe'],
      [__CLASS__, 'callMeStatic'],
      function () {
        return TRUE;
      },
      "I'm not callable",
    ]));
  }

  /**
   * Tests asserting all members are !empty().
   *
   * @covers ::assertAllNotEmpty
   */
  public function testAllNotEmpty(): void {
    $this->assertTrue(Inspector::assertAllNotEmpty([1, 'two']));
    $this->assertFalse(Inspector::assertAllNotEmpty(['']));
  }

  /**
   * Tests asserting all arguments are numbers or strings castable to numbers.
   *
   * @covers ::assertAllNumeric
   */
  public function testAssertAllNumeric(): void {
    $this->assertTrue(Inspector::assertAllNumeric([1, '2', 3.14]));
    $this->assertFalse(Inspector::assertAllNumeric([1, 'two', 3.14]));
  }

  /**
   * Tests asserting strstr() or stristr() match.
   *
   * @covers ::assertAllMatch
   */
  public function testAssertAllMatch(): void {
    $this->assertTrue(Inspector::assertAllMatch('f', ['fee', 'fi', 'fo']));
    $this->assertTrue(Inspector::assertAllMatch('F', ['fee', 'fi', 'fo']));
    $this->assertTrue(Inspector::assertAllMatch('f', ['fee', 'fi', 'fo'], TRUE));
    $this->assertFalse(Inspector::assertAllMatch('F', ['fee', 'fi', 'fo'], TRUE));
    $this->assertFalse(Inspector::assertAllMatch('e', ['fee', 'fi', 'fo']));
    $this->assertFalse(Inspector::assertAllMatch('1', [12]));
  }

  /**
   * Tests asserting regular expression match.
   *
   * @covers ::assertAllRegularExpressionMatch
   */
  public function testAssertAllRegularExpressionMatch(): void {
    $this->assertTrue(Inspector::assertAllRegularExpressionMatch('/f/i', ['fee', 'fi', 'fo']));
    $this->assertTrue(Inspector::assertAllRegularExpressionMatch('/F/i', ['fee', 'fi', 'fo']));
    $this->assertTrue(Inspector::assertAllRegularExpressionMatch('/f/', ['fee', 'fi', 'fo']));
    $this->assertFalse(Inspector::assertAllRegularExpressionMatch('/F/', ['fee', 'fi', 'fo']));
    $this->assertFalse(Inspector::assertAllRegularExpressionMatch('/e/', ['fee', 'fi', 'fo']));
    $this->assertFalse(Inspector::assertAllRegularExpressionMatch('/1/', [12]));
  }

  /**
   * Tests asserting all members are objects.
   *
   * @covers ::assertAllObjects
   */
  public function testAssertAllObjects(): void {
    $this->assertTrue(Inspector::assertAllObjects([new \ArrayObject(), new \ArrayObject()]));
    $this->assertFalse(Inspector::assertAllObjects([new \ArrayObject(), new \ArrayObject(), 'foo']));
    $this->assertTrue(Inspector::assertAllObjects([new \ArrayObject(), new \ArrayObject()], '\\Traversable'));
    $this->assertFalse(Inspector::assertAllObjects([new \ArrayObject(), new \ArrayObject(), 'foo'], '\\Traversable'));
    $this->assertFalse(Inspector::assertAllObjects([new \ArrayObject(), new StringObject()], '\\Traversable'));
    $this->assertTrue(Inspector::assertAllObjects([new \ArrayObject(), new StringObject()], '\\Traversable', '\\Drupal\\Tests\\Component\\Assertion\\StringObject'));
    $this->assertFalse(Inspector::assertAllObjects([new \ArrayObject(), new StringObject(), new \stdClass()], '\\ArrayObject', '\\Drupal\\Tests\\Component\\Assertion\\StringObject'));
  }

  /**
   * Defines a test method referenced by ::testAllCallable().
   */
  public function callMe() {
    return TRUE;
  }

  /**
   * Defines a test method referenced by ::testAllCallable().
   */
  public static function callMeStatic() {
    return TRUE;
  }

}

/**
 * Quick class for testing for objects with __toString.
 */
class StringObject {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'foo';
  }

}

<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\VariableTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Variable;
use PHPUnit\Framework\TestCase;

/**
 * Test variable export functionality in Variable component.
 *
 * @group Variable
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Variable
 */
class VariableTest extends TestCase {

  /**
   * A bogus callable for testing ::callableToString().
   */
  public static function fake(): void {
  }

  /**
   * Data provider for testCallableToString().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public function providerCallableToString(): array {
    $self = static::class;
    return [
      'string' => [
        "$self::fake",
        "$self::fake",
      ],
      'static method as array' => [
        [$self, 'fake'],
        "$self::fake",
      ],
      'closure' => [
        function () {
          return NULL;
        },
        '[closure]',
      ],
      'object method' => [
        [new static(), 'fake'],
        "$self::fake",
      ],
      'service method' => [
        'fake_service:method',
        'fake_service:method',
      ],
      'single-item array' => [
        ['some_function'],
        'some_function',
      ],
      'empty array' => [
        [],
        '[unknown]',
      ],
      'object' => [
        new \stdClass(),
        '[unknown]',
      ],
      'definitely not callable' => [
        TRUE,
        '[unknown]',
      ],
    ];
  }

  /**
   * Tests generating a human-readable name for a callable.
   *
   * @param callable $callable
   *   A callable.
   * @param string $expected_name
   *   The expected human-readable name of the callable.
   *
   * @dataProvider providerCallableToString
   *
   * @covers ::callableToString
   */
  public function testCallableToString($callable, string $expected_name): void {
    $this->assertSame($expected_name, Variable::callableToString($callable));
  }

  /**
   * Data provider for testExport().
   *
   * @return array
   *   An array containing:
   *     - The expected export string.
   *     - The variable to export.
   */
  public function providerTestExport() {
    return [
      // Array.
      [
        'array()',
        [],
      ],
      [
        // non-associative.
        "array(\n  1,\n  2,\n  3,\n  4,\n)",
        [1, 2, 3, 4],
      ],
      [
        // associative.
        "array(\n  'a' => 1,\n)",
        ['a' => 1],
      ],
      // Bool.
      [
        'TRUE',
        TRUE,
      ],
      [
        'FALSE',
        FALSE,
      ],
      // Strings.
      [
        "'string'",
        'string',
      ],
      [
        '"\n\r\t"',
        "\n\r\t",
      ],
      [
        // 2 backslashes. \\
        "'\\'",
        '\\',
      ],
      [
        // Double-quote "
        "'\"'",
        "\"",
      ],
      [
        // Single-quote '
        '"\'"',
        "'",
      ],
      [
        // Quotes with $ symbols.
        '"\$settings[\'foo\']"',
        '$settings[\'foo\']',
      ],
      // Object.
      [
        // A stdClass object.
        '(object) array()',
        new \stdClass(),
      ],
      [
        // A not-stdClass object.
        "Drupal\Tests\Component\Utility\StubVariableTestClass::__set_state(array(\n))",
        new StubVariableTestClass(),
      ],
    ];
  }

  /**
   * Tests exporting variables.
   *
   * @dataProvider providerTestExport
   * @covers ::export
   *
   * @param string $expected
   *   The expected exported variable.
   * @param mixed $variable
   *   The variable to be exported.
   */
  public function testExport($expected, $variable) {
    $this->assertEquals($expected, Variable::export($variable));
  }

}

/**
 * No-op test class for VariableTest::testExport().
 *
 * @see Drupal\Tests\Component\Utility\VariableTest::testExport()
 * @see Drupal\Tests\Component\Utility\VariableTest::providerTestExport()
 */
class StubVariableTestClass {

}

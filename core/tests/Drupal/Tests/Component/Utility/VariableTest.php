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

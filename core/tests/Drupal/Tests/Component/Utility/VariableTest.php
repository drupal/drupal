<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\VariableTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\Variable;

/**
 * Test variable export functionality in Variable component.
 *
 * @group Variable
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Variable
 */
class VariableTest extends UnitTestCase {

  /**
   * Data provider for testExport().
   *
   * @return array
   *   An array containing:
   *     - The expected export string.
   *     - The variable to export.
   */
  public function providerTestExport() {
    return array(
      // Array.
      array(
        'array()',
        array(),
      ),
      array(
        // non-associative.
        "array(\n  1,\n  2,\n  3,\n  4,\n)",
        array(1, 2, 3, 4),
      ),
      array(
        // associative.
        "array(\n  'a' => 1,\n)",
        array('a' => 1),
      ),
      // Bool.
      array(
        'TRUE',
        TRUE,
      ),
      array(
        'FALSE',
        FALSE,
      ),
      // Strings.
      array(
        "'string'",
        'string',
      ),
      array(
        '"\n\r\t"',
        "\n\r\t",
      ),
      array(
        // 2 backslashes. \\
        "'\\'",
        '\\',
      ),
      array(
        // Double-quote "
        "'\"'",
        "\"",
      ),
      array(
        // Single-quote '
        '"\'"',
        "'",
      ),
      // Object.
      array(
        // A stdClass object.
        '(object) array()',
        new \stdClass(),
      ),
      array(
        // A not-stdClass object.
        "Drupal\Tests\Component\Utility\StubVariableTestClass::__set_state(array(\n))",
        new StubVariableTestClass(),
      ),
    );
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

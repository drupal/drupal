<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\InfoFileParserUnitTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\UnitTestBase;

class InfoFileParserUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Info file format parser',
      'description' => 'Tests proper parsing of a .info file formatted string.',
      'group' => 'System',
    );
  }

  /**
   * Test drupal_parse_info_format().
   */
  function testDrupalParseInfoFormat() {
    $config = '
simple = Value
quoted = " Value"
multiline = "Value
  Value"
array[] = Value1
array[] = Value2
array_assoc[a] = Value1
array_assoc[b] = Value2
array_deep[][][] = Value
array_deep_assoc[a][b][c] = Value
array_space[a b] = Value';

    $expected = array(
      'simple' => 'Value',
      'quoted' => ' Value',
      'multiline' => "Value\n  Value",
      'array' => array(
        0 => 'Value1',
        1 => 'Value2',
      ),
      'array_assoc' => array(
        'a' => 'Value1',
        'b' => 'Value2',
      ),
      'array_deep' => array(
        0 => array(
          0 => array(
            0 => 'Value',
          ),
        ),
      ),
      'array_deep_assoc' => array(
        'a' => array(
          'b' => array(
            'c' => 'Value',
          ),
        ),
      ),
      'array_space' => array(
        'a b' => 'Value',
      ),
    );

    $parsed = drupal_parse_info_format($config);

    $this->assertEqual($parsed['simple'], $expected['simple'], t('Set a simple value.'));
    $this->assertEqual($parsed['quoted'], $expected['quoted'], t('Set a simple value in quotes.'));
    $this->assertEqual($parsed['multiline'], $expected['multiline'], t('Set a multiline value.'));
    $this->assertEqual($parsed['array'], $expected['array'], t('Set a simple array.'));
    $this->assertEqual($parsed['array_assoc'], $expected['array_assoc'], t('Set an associative array.'));
    $this->assertEqual($parsed['array_deep'], $expected['array_deep'], t('Set a nested array.'));
    $this->assertEqual($parsed['array_deep_assoc'], $expected['array_deep_assoc'], t('Set a nested associative array.'));
    $this->assertEqual($parsed['array_space'], $expected['array_space'], t('Set an array with a whitespace in the key.'));
    $this->assertEqual($parsed, $expected, t('Entire parsed .info string and expected array are identical.'));
  }
}

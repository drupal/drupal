<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\SizeUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests file size parsing and formatting functions.
 */
class SizeUnitTest extends UnitTestBase {
  protected $exact_test_cases;
  protected $rounded_test_cases;

  public static function getInfo() {
    return array(
      'name' => 'Size parsing test',
      'description' => 'Parse a predefined amount of bytes and compare the output with the expected value.',
      'group' => 'Common',
    );
  }

  function setUp() {
    $kb = DRUPAL_KILOBYTE;
    $this->exact_test_cases = array(
      '1 byte' => 1,
      '1 KB'   => $kb,
      '1 MB'   => $kb * $kb,
      '1 GB'   => $kb * $kb * $kb,
      '1 TB'   => $kb * $kb * $kb * $kb,
      '1 PB'   => $kb * $kb * $kb * $kb * $kb,
      '1 EB'   => $kb * $kb * $kb * $kb * $kb * $kb,
      '1 ZB'   => $kb * $kb * $kb * $kb * $kb * $kb * $kb,
      '1 YB'   => $kb * $kb * $kb * $kb * $kb * $kb * $kb * $kb,
    );
    $this->rounded_test_cases = array(
      '2 bytes' => 2,
      '1 MB' => ($kb * $kb) - 1, // rounded to 1 MB (not 1000 or 1024 kilobyte!)
      round(3623651 / ($this->exact_test_cases['1 MB']), 2) . ' MB' => 3623651, // megabytes
      round(67234178751368124 / ($this->exact_test_cases['1 PB']), 2) . ' PB' => 67234178751368124, // petabytes
      round(235346823821125814962843827 / ($this->exact_test_cases['1 YB']), 2) . ' YB' => 235346823821125814962843827, // yottabytes
    );
    parent::setUp();
  }

  /**
   * Checks that format_size() returns the expected string.
   */
  function testCommonFormatSize() {
    foreach (array($this->exact_test_cases, $this->rounded_test_cases) as $test_cases) {
      foreach ($test_cases as $expected => $input) {
        $this->assertEqual(
          ($result = format_size($input, NULL)),
          $expected,
          $expected . ' == ' . $result . ' (' . $input . ' bytes)'
        );
      }
    }
  }

  /**
   * Checks that parse_size() returns the proper byte sizes.
   */
  function testCommonParseSize() {
    foreach ($this->exact_test_cases as $string => $size) {
      $this->assertEqual(
        $parsed_size = parse_size($string),
        $size,
        $size . ' == ' . $parsed_size . ' (' . $string . ')'
      );
    }

    // Some custom parsing tests
    $string = '23476892 bytes';
    $this->assertEqual(
      ($parsed_size = parse_size($string)),
      $size = 23476892,
      $string . ' == ' . $parsed_size . ' bytes'
    );
    $string = '76MRandomStringThatShouldBeIgnoredByParseSize.'; // 76 MB
    $this->assertEqual(
      $parsed_size = parse_size($string),
      $size = 79691776,
      $string . ' == ' . $parsed_size . ' bytes'
    );
    $string = '76.24 Giggabyte'; // Misspeld text -> 76.24 GB
    $this->assertEqual(
      $parsed_size = parse_size($string),
      $size = 81862076662,
      $string . ' == ' . $parsed_size . ' bytes'
    );
  }

  /**
   * Cross-tests parse_size() and format_size().
   */
  function testCommonParseSizeFormatSize() {
    foreach ($this->exact_test_cases as $size) {
      $this->assertEqual(
        $size,
        ($parsed_size = parse_size($string = format_size($size, NULL))),
        $size . ' == ' . $parsed_size . ' (' . $string . ')'
      );
    }
  }
}

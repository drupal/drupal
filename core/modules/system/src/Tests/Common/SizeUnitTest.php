<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\SizeUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Component\Utility\Bytes;
use Drupal\simpletest\KernelTestBase;

/**
 * Parse a predefined amount of bytes and compare the output with the expected
 * value.
 *
 * @group Common
 */
class SizeUnitTest extends KernelTestBase {
  protected $exactTestCases;
  protected $roundedTestCases;

  protected function setUp() {
    parent::setUp();
    $kb = Bytes::KILOBYTE;
    $this->exactTestCases = array(
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
    $this->roundedTestCases = array(
      '2 bytes' => 2,
      '1 MB' => ($kb * $kb) - 1, // rounded to 1 MB (not 1000 or 1024 kilobyte!)
      round(3623651 / ($this->exactTestCases['1 MB']), 2) . ' MB' => 3623651, // megabytes
      round(67234178751368124 / ($this->exactTestCases['1 PB']), 2) . ' PB' => 67234178751368124, // petabytes
      round(235346823821125814962843827 / ($this->exactTestCases['1 YB']), 2) . ' YB' => 235346823821125814962843827, // yottabytes
    );
  }

  /**
   * Checks that format_size() returns the expected string.
   */
  function testCommonFormatSize() {
    foreach (array($this->exactTestCases, $this->roundedTestCases) as $test_cases) {
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
   * Cross-tests Bytes::toInt() and format_size().
   */
  function testCommonParseSizeFormatSize() {
    foreach ($this->exactTestCases as $size) {
      $this->assertEqual(
        $size,
        ($parsed_size = Bytes::toInt($string = format_size($size, NULL))),
        $size . ' == ' . $parsed_size . ' (' . $string . ')'
      );
    }
  }
}

<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\Component\Utility\Bytes;
use Drupal\KernelTests\KernelTestBase;

/**
 * Parse a predefined amount of bytes and compare the output with the expected
 * value.
 *
 * @group Common
 */
class SizeTest extends KernelTestBase {
  protected $exactTestCases;
  protected $roundedTestCases;

  protected function setUp() {
    parent::setUp();
    $kb = Bytes::KILOBYTE;
    $this->exactTestCases = [
      '1 byte' => 1,
      '1 KB'   => $kb,
      '1 MB'   => $kb * $kb,
      '1 GB'   => $kb * $kb * $kb,
      '1 TB'   => $kb * $kb * $kb * $kb,
      '1 PB'   => $kb * $kb * $kb * $kb * $kb,
      '1 EB'   => $kb * $kb * $kb * $kb * $kb * $kb,
      '1 ZB'   => $kb * $kb * $kb * $kb * $kb * $kb * $kb,
      '1 YB'   => $kb * $kb * $kb * $kb * $kb * $kb * $kb * $kb,
    ];
    $this->roundedTestCases = [
      '2 bytes' => 2,
      // Rounded to 1 MB (not 1000 or 1024 kilobyte!).
      '1 MB' => ($kb * $kb) - 1,
      // Megabytes.
      round(3623651 / ($this->exactTestCases['1 MB']), 2) . ' MB' => 3623651,
      // Petabytes.
      round(67234178751368124 / ($this->exactTestCases['1 PB']), 2) . ' PB' => 67234178751368124,
      // Yottabytes.
      round(235346823821125814962843827 / ($this->exactTestCases['1 YB']), 2) . ' YB' => 235346823821125814962843827,
    ];
  }

  /**
   * Checks that format_size() returns the expected string.
   */
  public function testCommonFormatSize() {
    foreach ([$this->exactTestCases, $this->roundedTestCases] as $test_cases) {
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
  public function testCommonParseSizeFormatSize() {
    foreach ($this->exactTestCases as $size) {
      $this->assertEqual(
        $size,
        ($parsed_size = Bytes::toInt($string = format_size($size, NULL))),
        $size . ' == ' . $parsed_size . ' (' . $string . ')'
      );
    }
  }

}

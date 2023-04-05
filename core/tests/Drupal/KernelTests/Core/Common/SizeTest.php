<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\Component\Utility\Bytes;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests format_size().
 *
 * @group Common
 */
class SizeTest extends KernelTestBase {

  /**
   * Checks that format_size() returns the expected string.
   *
   * @dataProvider providerTestCommonFormatSize
   */
  public function testCommonFormatSize($expected, $input) {
    $size = format_size($input, NULL);
    $this->assertEquals($expected, $size);
  }

  /**
   * Provides a list of byte size to test.
   */
  public function providerTestCommonFormatSize() {
    $kb = Bytes::KILOBYTE;
    return [
      ['0 bytes', 0],
      ['1 byte', 1],
      ['-1 bytes', -1],
      ['2 bytes', 2],
      ['-2 bytes', -2],
      ['1023 bytes', $kb - 1],
      ['1 KB', $kb],
      ['1 MB', pow($kb, 2)],
      ['1 GB', pow($kb, 3)],
      ['1 TB', pow($kb, 4)],
      ['1 PB', pow($kb, 5)],
      ['1 EB', pow($kb, 6)],
      ['1 ZB', pow($kb, 7)],
      ['1 YB', pow($kb, 8)],
      ['1024 YB', pow($kb, 9)],
      // Rounded to 1 MB - not 1000 or 1024 kilobytes
      ['1 MB', ($kb * $kb) - 1],
      ['-1 MB', -(($kb * $kb) - 1)],
      // Decimal Megabytes
      ['3.46 MB', 3623651],
      ['3.77 GB', 4053371676],
      // Decimal Petabytes
      ['59.72 PB', 67234178751368124],
      // Decimal Yottabytes
      ['194.67 YB', 235346823821125814962843827],
    ];
  }

}

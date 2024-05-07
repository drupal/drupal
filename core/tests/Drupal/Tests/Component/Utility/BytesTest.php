<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Bytes;
use Drupal\TestTools\Extension\DeprecationBridge\ExpectDeprecationTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests bytes size parsing helper methods.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Bytes
 */
class BytesTest extends TestCase {

  use ExpectDeprecationTrait;
  use ProphecyTrait;

  /**
   * Tests \Drupal\Component\Utility\Bytes::toNumber().
   *
   * @param string $size
   *   The value for the size argument for
   *   \Drupal\Component\Utility\Bytes::toNumber().
   * @param float $expected_number
   *   The expected return value from
   *   \Drupal\Component\Utility\Bytes::toNumber().
   *
   * @dataProvider providerTestToNumber
   * @covers ::toNumber
   */
  public function testToNumber($size, float $expected_number): void {
    $this->assertSame($expected_number, Bytes::toNumber($size));
  }

  /**
   * Provides data for testToNumber().
   *
   * @return array
   *   An array of arrays, each containing the argument for
   *   \Drupal\Component\Utility\Bytes::toNumber(): size, and the expected
   *   return value with the expected type (float).
   */
  public static function providerTestToNumber(): array {
    return [
      ['1', 1.0],
      ['1 byte', 1.0],
      ['1 KB'  , (float) Bytes::KILOBYTE],
      ['1 MB'  , (float) pow(Bytes::KILOBYTE, 2)],
      ['1 GB'  , (float) pow(Bytes::KILOBYTE, 3)],
      ['1 TB'  , (float) pow(Bytes::KILOBYTE, 4)],
      ['1 PB'  , (float) pow(Bytes::KILOBYTE, 5)],
      ['1 EB'  , (float) pow(Bytes::KILOBYTE, 6)],
      // Zettabytes and yottabytes cannot be represented by integers on 64-bit
      // systems, so pow() returns a float.
      ['1 ZB'  , pow(Bytes::KILOBYTE, 7)],
      ['1 YB'  , pow(Bytes::KILOBYTE, 8)],
      ['23476892 bytes', 23476892.0],
      // 76 MB.
      ['76MRandomStringThatShouldBeIgnoredByParseSize.', 79691776.0],
      // cspell:ignore giggabyte
      // 76.24 GB (with typo).
      ['76.24 Giggabyte', 81862076662.0],
      ['1.5', 2.0],
      [1.5, 2.0],
      ['2.4', 2.0],
      [2.4, 2.0],
      ['', 0.0],
      ['9223372036854775807', 9223372036854775807.0],
    ];
  }

  /**
   * Tests \Drupal\Component\Utility\Bytes::validate().
   *
   * @param string $string
   *   The value for the string argument for
   *   \Drupal\Component\Utility\Bytes::validate().
   * @param bool $expected_result
   *   The expected return value from
   *   \Drupal\Component\Utility\Bytes::validate().
   *
   * @dataProvider providerTestValidate
   * @covers ::validate
   * @covers ::validateConstraint
   */
  public function testValidate($string, bool $expected_result): void {
    $this->assertSame($expected_result, Bytes::validate($string));

    $execution_context = $this->prophesize(ExecutionContextInterface::class);
    if ($expected_result) {
      $execution_context->addViolation(Argument::cetera())
        ->shouldNotBeCalled();
    }
    else {
      $execution_context->addViolation(Argument::cetera())
        ->shouldBeCalledTimes(1);
    }
    Bytes::validateConstraint($string, $execution_context->reveal());
  }

  /**
   * Provides data for testValidate().
   *
   * @return array
   *   An array of arrays, each containing the argument for
   *   \Drupal\Component\Utility\Bytes::validate(): string, and the expected
   *   return value with the expected type (bool).
   */
  public static function providerTestValidate(): array {
    return [
      // String not starting with a number.
      ['foo', FALSE],
      ['fifty megabytes', FALSE],
      ['five', FALSE],
      // Test spaces and capital combinations.
      [5, TRUE],
      ['5M', TRUE],
      ['5m', TRUE],
      ['5 M', TRUE],
      ['5 m', TRUE],
      ['5Mb', TRUE],
      ['5mb', TRUE],
      ['5 Mb', TRUE],
      ['5 mb', TRUE],
      ['5Gb', TRUE],
      ['5gb', TRUE],
      ['5 Gb', TRUE],
      ['5 gb', TRUE],
      // Test all allowed suffixes.
      ['5', TRUE],
      ['5 b', TRUE],
      ['5 byte', TRUE],
      ['5 bytes', TRUE],
      ['5 k', TRUE],
      ['5 kb', TRUE],
      ['5 kilobyte', TRUE],
      ['5 kilobytes', TRUE],
      ['5 m', TRUE],
      ['5 mb', TRUE],
      ['5 megabyte', TRUE],
      ['5 megabytes', TRUE],
      ['5 g', TRUE],
      ['5 gb', TRUE],
      ['5 gigabyte', TRUE],
      ['5 gigabytes', TRUE],
      ['5 t', TRUE],
      ['5 tb', TRUE],
      ['5 terabyte', TRUE],
      ['5 terabytes', TRUE],
      ['5 p', TRUE],
      ['5 pb', TRUE],
      ['5 petabyte', TRUE],
      ['5 petabytes', TRUE],
      ['5 e', TRUE],
      ['5 eb', TRUE],
      ['5 exabyte', TRUE],
      ['5 exabytes', TRUE],
      ['5 z', TRUE],
      ['5 zb', TRUE],
      ['5 zettabyte', TRUE],
      ['5 zettabytes', TRUE],
      ['5 y', TRUE],
      ['5 yb', TRUE],
      ['5 yottabyte', TRUE],
      ['5 yottabytes', TRUE],
      // Test with decimal.
      [5.1, TRUE],
      ['5.1M', TRUE],
      ['5.1mb', TRUE],
      ['5.1 M', TRUE],
      ['5.1 Mb', TRUE],
      ['5.1 megabytes', TRUE],
      // Test with an unauthorized string.
      ['1five', FALSE],
      ['1 1 byte', FALSE],
      ['1,1 byte', FALSE],
      // Test with leading and trailing spaces.
      [' 5.1mb', FALSE],
      ['5.1mb ', TRUE],
      [' 5.1mb ', FALSE],
      [' 5.1 megabytes', FALSE],
      ['5.1 megabytes ', TRUE],
      [' 5.1 megabytes ', FALSE],
      ['300 0', FALSE],
    ];
  }

}

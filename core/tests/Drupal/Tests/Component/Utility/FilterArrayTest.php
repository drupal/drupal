<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\FilterArray;
use PHPUnit\Framework\TestCase;

/**
 * Test filter array functions.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\FilterArray
 */
class FilterArrayTest extends TestCase {

  /**
   * Tests removing empty strings.
   *
   * @dataProvider providerRemoveEmptyStrings
   * @covers ::removeEmptyStrings
   */
  public function testRemoveEmptyStrings(array $values, array $expected): void {
    $this->assertEquals($expected, array_values(FilterArray::removeEmptyStrings($values)));
  }

  /**
   * Data provider for testRemoveEmptyStrings().
   *
   * @see testRemoveEmptyStrings()
   */
  public static function providerRemoveEmptyStrings(): \Generator {
    yield 'strings' => [
      ['', ' ', '0', 'true', 'false'],
      [' ', '0', 'true', 'false'],
    ];
    yield 'integers' => [
      [-1, 0, 1],
      [-1, 0, 1],
    ];
    yield 'null, true, false' => [
      [NULL, TRUE, FALSE],
      [TRUE],
    ];

    $stringable = new class implements \Stringable {

      public function __toString(): string {
        return 'foo';
      }

    };

    yield 'non-scalar' => [
      [new $stringable()],
      [new $stringable()],
    ];
  }

}

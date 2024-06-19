<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Unit;

use Drupal\media\OEmbed\Resource;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\media\OEmbed\Resource
 * @group media
 */
class ResourceTest extends UnitTestCase {

  /**
   * Test cases for ::testSetDimensions.
   */
  public static function setDimensionsTestCases() {
    return [
      'Standard rich dimensions' => [
        'rich',
        5,
        10,
      ],
      'Negative width and height' => [
        'rich',
        -5,
        -10,
        'The dimensions must be NULL or numbers greater than zero.',
      ],
      'Zero width' => [
        'rich',
        0,
        5,
        'The dimensions must be NULL or numbers greater than zero.',
      ],
      'NULL width' => [
        'rich',
        NULL,
        10,
      ],
      'NULL height' => [
        'rich',
        NULL,
        10,
      ],
      'NULL width and height' => [
        'rich',
        NULL,
        NULL,
      ],
      'Cast numeric dimensions' => [
        'rich',
        "1",
        "45",
        NULL,
        1,
        45,
      ],
      'Cast invalid zero value' => [
        'rich',
        "0",
        10,
        'The dimensions must be NULL or numbers greater than zero.',
      ],
      'Cast negative value' => [
        'rich',
        "-10",
        10,
        'The dimensions must be NULL or numbers greater than zero.',
      ],
    ];
  }

  /**
   * @covers ::setDimensions
   * @dataProvider setDimensionsTestCases
   */
  public function testSetDimensions($factory, $width, $height, $exception = NULL, $expected_width = NULL, $expected_height = NULL): void {
    if ($exception) {
      $this->expectException(\InvalidArgumentException::class);
      $this->expectExceptionMessage($exception);
    }
    $resource = Resource::$factory('foo', $width, $height);
    $this->assertSame($expected_width ?: $width, $resource->getWidth());
    $this->assertSame($expected_height ?: $height, $resource->getHeight());
  }

}

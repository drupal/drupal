<?php

declare(strict_types=1);

namespace Drupal\Tests\sdc\Unit;

use Drupal\sdc\Utilities;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Utilities class.
 *
 * @coversDefaultClass \Drupal\sdc\Utilities
 *
 * @group sdc
 */
final class UtilitiesTest extends TestCase {

  /**
   * @covers ::isRenderArray
   * @dataProvider dataProviderIsRenderArray
   */
  public function testIsRenderArray($build, $expected) {
    $this->assertSame(
      $expected,
      Utilities::isRenderArray($build)
    );
  }

  public function dataProviderIsRenderArray() {
    return [
      'valid markup render array' => [['#markup' => 'hello world'], TRUE],
      'invalid "foo" string' => [['foo', '#markup' => 'hello world'], FALSE],
      'null is not an array' => [NULL, FALSE],
      'an empty array is not a render array' => [[], FALSE],
      'funny enough a key with # is valid' => [['#' => TRUE], TRUE],
      'nested arrays can be valid too' => [['one' => [2 => ['#three' => 'charm!']]], TRUE],
    ];
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\olivero\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the _olivero_hex_to_hsl() function.
 *
 * @group olivero
 */
final class OliveroHexToHslTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../olivero.theme';
  }

  /**
   * Tests hex to HSL conversion.
   *
   * @param string $hex
   *   The hex code.
   * @param array $expected_hsl
   *   The expected HSL values.
   *
   * @dataProvider hexCodes
   */
  public function testHexToHsl(string $hex, array $expected_hsl): void {
    self::assertEquals($expected_hsl, _olivero_hex_to_hsl($hex));
  }

  /**
   * Data provider of hex codes and HSL values.
   *
   * @return array[]
   *   The test data.
   */
  public function hexCodes(): array {
    return [
      'Blue Lagoon' => ['#1b9ae4', [202, 79, 50]],
      'Firehouse' => ['#a30f0f', [0, 83, 35]],
      'Ice' => ['#57919e', [191, 29, 48]],
      'Plum' => ['#7a4587', [288, 32, 40]],
      'Slate' => ['#47625b', [164, 16, 33]],
    ];
  }

}

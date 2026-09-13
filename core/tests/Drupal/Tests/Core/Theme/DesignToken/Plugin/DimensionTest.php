<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken\Plugin;

use Drupal\Core\Theme\DesignToken\Enum\DimensionUnit;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Dimension;
use Drupal\design_tokens_test\Enum\TestDimensionUnit;
use Drupal\Tests\Core\Theme\DesignToken\DesignTokenValuePluginTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests dimension value plugin.
 */
#[CoversClass(Dimension::class)]
#[Group('design_token')]
class DimensionTest extends DesignTokenValuePluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $pluginId = 'dimension';

  /**
   * {@inheritdoc}
   */
  #[DataProvider('providerToDtcg')]
  public function testToDtcg(mixed $value, mixed $expected): void {
    $this->assertIsArray($value);
    $this->assertIsArray($expected);
    $plugin = new Dimension($value, $this->pluginId, []);
    $this->assertSame($expected, $plugin->toDtcg());
  }

  /**
   * Data provider for ::testToDtcg().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToDtcg(): iterable {
    yield 'px' => [
      [
        'value' => 12,
        'unit' => 'px',
      ],
      [
        'value' => 12,
        'unit' => 'px',
      ],
    ];

    yield 'rem' => [
      [
        'value' => 2,
        'unit' => 'rem',
      ],
      [
        'value' => 2,
        'unit' => 'rem',
      ],
    ];

    yield 'with enum' => [
      [
        'value' => 2,
        'unit' => DimensionUnit::Px,
      ],
      [
        'value' => 2,
        'unit' => 'px',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[DataProvider('providerToCss')]
  public function testToCss(mixed $value, string $expected): void {
    $this->assertIsArray($value);
    $plugin = new Dimension($value, $this->pluginId, []);
    $this->assertSame($expected, $plugin->toCss());
  }

  /**
   * Data provider for ::testToCss().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToCss(): iterable {
    yield 'px' => [
      [
        'value' => 12,
        'unit' => 'px',
      ],
      '12px',
    ];

    yield 'rem' => [
      [
        'value' => 2,
        'unit' => 'rem',
      ],
      '2rem',
    ];
  }

  /**
   * Tests invalid unit.
   */
  public function testInvalidUnit(): void {
    $this->expectException(\ValueError::class);
    $this->expectExceptionMessage('"<script>alert()</script>" is not a valid backing value for enum Drupal\Core\Theme\DesignToken\Enum\DimensionUnit');
    $configuration = [
      'value' => 2,
      'unit' => '<script>alert()</script>',
    ];
    new Dimension($configuration, $this->pluginId, []);
  }

  /**
   * Tests creating a dimension plugin with an enum unit.
   */
  public function testEnumUnit(): void {
    $configuration = [
      'value' => 2,
      'unit' => DimensionUnit::Rem,
    ];
    $plugin = new Dimension($configuration, $this->pluginId, []);
    $this->assertEquals(DimensionUnit::Rem, $plugin->getConfiguration()['unit']);

    // Tests with another DimensionUnitInterface than the default one.
    $configuration = [
      'value' => 2,
      'unit' => TestDimensionUnit::Em,
    ];
    $plugin = new Dimension($configuration, $this->pluginId, []);
    $this->assertEquals(TestDimensionUnit::Em, $plugin->getConfiguration()['unit']);
  }

}

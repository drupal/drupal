<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken\Plugin;

use Drupal\Core\Theme\DesignToken\Enum\FontWeightAlias;
use Drupal\Core\Theme\Plugin\DesignTokenValue\FontWeight;
use Drupal\Tests\Core\Theme\DesignToken\DesignTokenValuePluginTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests font weight value plugin.
 */
#[CoversClass(FontWeight::class)]
#[Group('design_token')]
class FontWeightTest extends DesignTokenValuePluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $pluginId = 'fontWeight';

  /**
   * {@inheritdoc}
   */
  #[DataProvider('providerToDtcg')]
  public function testToDtcg(mixed $value, mixed $expected): void {
    assert(is_string($expected) || is_int($expected));
    $configuration = [
      'value' => $value,
    ];
    $plugin = new FontWeight($configuration, $this->pluginId, []);
    $this->assertSame($expected, $plugin->toDtcg());
  }

  /**
   * Data provider for ::testToDtcg().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToDtcg(): iterable {
    foreach (FontWeightAlias::cases() as $case) {
      yield $case->name => [
        $case->value,
        $case->value(),
      ];
    }

    yield 'integer' => [
      50,
      50,
    ];

    yield 'minimum' => [
      1,
      1,
    ];

    yield 'maximum' => [
      1000,
      1000,
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[DataProvider('providerToCss')]
  public function testToCss(mixed $value, string $expected): void {
    $configuration = [
      'value' => $value,
    ];
    $plugin = new FontWeight($configuration, $this->pluginId, []);
    $this->assertSame($expected, $plugin->toCss());
  }

  /**
   * Data provider for ::testToCss().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToCss(): iterable {
    foreach (FontWeightAlias::cases() as $case) {
      yield $case->name => [
        $case->value,
        (string) $case->value(),
      ];
    }

    yield 'integer' => [
      50,
      '50',
    ];

    yield 'minimum' => [
      1,
      '1',
    ];

    yield 'maximum' => [
      1000,
      '1000',
    ];
  }

  /**
   * Tests invalid unit.
   */
  public function testInvalidUnit(): void {
    $this->expectException(\ValueError::class);
    $this->expectExceptionMessage('"<script>alert()</script>" is not a valid backing value for enum Drupal\Core\Theme\DesignToken\Enum\FontWeightAlias');
    $configuration = [
      'value' => '<script>alert()</script>',
    ];
    new FontWeight($configuration, $this->pluginId, []);
  }

}

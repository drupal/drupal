<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken\Plugin;

use Drupal\Core\Theme\Plugin\DesignTokenValue\Number;
use Drupal\Tests\Core\Theme\DesignToken\DesignTokenValuePluginTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests number value plugin.
 */
#[CoversClass(Number::class)]
#[Group('design_token')]
class NumberTest extends DesignTokenValuePluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $pluginId = 'number';

  /**
   * {@inheritdoc}
   */
  #[DataProvider('providerToDtcg')]
  public function testToDtcg(mixed $value, mixed $expected): void {
    $configuration = [
      'value' => $value,
    ];
    $plugin = new Number($configuration, $this->pluginId, []);
    $this->assertSame($expected, $plugin->toDtcg());
  }

  /**
   * Data provider for ::testToDtcg().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToDtcg(): iterable {
    yield 'integer' => [
      50,
      50,
    ];

    yield 'float' => [
      4.2,
      4.2,
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
    $plugin = new Number($configuration, $this->pluginId, []);
    $this->assertSame($expected, $plugin->toCss());
  }

  /**
   * Data provider for ::testToCss().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToCss(): iterable {
    yield 'integer' => [
      50,
      '50',
    ];

    yield 'float' => [
      4.2,
      '4.2',
    ];
  }

}

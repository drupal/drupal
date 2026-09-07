<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken\Plugin;

use Drupal\Core\Theme\Plugin\DesignTokenValue\FontFamily;
use Drupal\Tests\Core\Theme\DesignToken\DesignTokenValuePluginTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests font family value plugin.
 */
#[CoversClass(FontFamily::class)]
#[Group('design_token')]
class FontFamilyTest extends DesignTokenValuePluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $pluginId = 'fontFamily';

  /**
   * {@inheritdoc}
   */
  #[DataProvider('providerToDtcg')]
  public function testToDtcg(mixed $value, mixed $expected): void {
    $this->assertIsArray($expected);
    $configuration = [
      'value' => $value,
    ];
    $plugin = new FontFamily($configuration, $this->pluginId, []);
    $this->assertSame($expected, $plugin->toDtcg());
  }

  /**
   * Data provider for ::testToDtcg().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToDtcg(): iterable {
    yield 'string' => [
      'Arial',
      ['Arial'],
    ];

    yield 'list' => [
      ['Arial', 'sans-serif'],
      ['Arial', 'sans-serif'],
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
    $plugin = new FontFamily($configuration, $this->pluginId, []);
    $this->assertSame($expected, $plugin->toCss());
  }

  /**
   * Data provider for ::testToCss().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToCss(): iterable {
    yield 'string' => [
      'Arial',
      'Arial',
    ];

    yield 'list' => [
      ['Arial', 'sans-serif'],
      'Arial, sans-serif',
    ];

    yield 'XSS' => [
      ['Arial', '<script>alert()</script>'],
      'Arial, &lt;script&gt;alert()&lt;/script&gt;',
    ];
  }

}

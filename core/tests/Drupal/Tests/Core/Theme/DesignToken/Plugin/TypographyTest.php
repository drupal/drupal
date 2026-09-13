<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken\Plugin;

use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginBase;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManagerInterface;
use Drupal\Core\Theme\DesignToken\Enum\DimensionUnit;
use Drupal\Core\Theme\DesignToken\Enum\FontWeightAlias;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Dimension;
use Drupal\Core\Theme\Plugin\DesignTokenValue\FontFamily;
use Drupal\Core\Theme\Plugin\DesignTokenValue\FontWeight;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Number;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Typography;
use Drupal\Tests\Core\Theme\DesignToken\DesignTokenValuePluginTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests typography value plugin.
 */
#[CoversClass(Typography::class)]
#[Group('design_token')]
class TypographyTest extends DesignTokenValuePluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected string $pluginId = 'typography';

  /**
   * The typography's properties.
   *
   * @var array|string[]
   */
  protected array $properties = [
    'fontFamily' => 'fontFamily',
    'fontSize' => 'dimension',
    'fontWeight' => 'fontWeight',
    'letterSpacing' => 'dimension',
    'lineHeight' => 'number',
  ];

  /**
   * {@inheritdoc}
   */
  #[DataProvider('providerToDtcg')]
  public function testToDtcg(mixed $value, mixed $expected): void {
    $this->assertIsArray($value);
    $this->assertIsArray($expected);

    $this->setupContainer($value);
    $plugin = new Typography($value, $this->pluginId, [], \Drupal::service(DesignTokenValuePluginManagerInterface::class));
    $this->assertSame($expected, $plugin->toDtcg());
  }

  /**
   * Data provider for ::testToDtcg().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToDtcg(): iterable {
    yield 'default' => [
      [
        'fontFamily' => 'Arial',
        'fontSize' => [
          'value' => 0,
          'unit' => 'px',
        ],
        'fontWeight' => 20,
        'letterSpacing' => [
          'value' => 0,
          'unit' => 'px',
        ],
        'lineHeight' => 0,
      ],
      [
        'fontFamily' => ['Arial'],
        'fontSize' => [
          'value' => 0,
          'unit' => 'px',
        ],
        'fontWeight' => 20,
        'letterSpacing' => [
          'value' => 0,
          'unit' => 'px',
        ],
        'lineHeight' => 0,
      ],
    ];
    yield 'with enum and other structure' => [
      [
        'fontFamily' => 'Arial',
        'fontSize' => [
          'value' => 10,
          'unit' => DimensionUnit::Px,
        ],
        'fontWeight' => FontWeightAlias::Regular,
        'letterSpacing' => [
          'value' => 30,
          'unit' => dimensionUnit::Px,
        ],
        'lineHeight' => 5,
      ],
      [
        'fontFamily' => ['Arial'],
        'fontSize' => [
          'value' => 10,
          'unit' => 'px',
        ],
        'fontWeight' => 400,
        'letterSpacing' => [
          'value' => 30,
          'unit' => 'px',
        ],
        'lineHeight' => 5,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[DataProvider('providerToCss')]
  public function testToCss(mixed $value, string $expected): void {
    $this->assertIsArray($value);
    $this->setupContainer($value);
    $plugin = new Typography($value, $this->pluginId, [], \Drupal::service(DesignTokenValuePluginManagerInterface::class));
    $this->assertSame($expected, $plugin->toCss());
  }

  /**
   * Data provider for ::testToCss().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToCss(): iterable {
    yield 'default' => [
      [
        'fontFamily' => ['Arial', 'sans-serif'],
        'fontSize' => [
          'value' => 10,
          'unit' => 'px',
        ],
        'fontWeight' => 20,
        'letterSpacing' => [
          'value' => 30,
          'unit' => 'px',
        ],
        'lineHeight' => 5,
      ],
      '20 10px/5 Arial, sans-serif',
    ];
  }

  /**
   * Sets up service container as needed by the plugin.
   *
   * @param array $configuration
   *   The typography plugin configuration.
   */
  protected function setupContainer(array $configuration): void {
    $properties = $this->properties;
    $instances = [];
    foreach ($properties as $property => $propertyType) {
      $propertyConfig = DesignTokenValuePluginBase::prepareConfiguration($configuration[$property]);
      switch ($propertyType) {
        case 'dimension':
          $instances[$property] = new Dimension($propertyConfig, $propertyType, []);
          break;

        case 'fontFamily':
          $instances[$property] = new FontFamily($propertyConfig, $propertyType, []);
          break;

        case 'fontWeight':
          $instances[$property] = new FontWeight($propertyConfig, $propertyType, []);
          break;

        case 'number':
          $instances[$property] = new Number($propertyConfig, $propertyType, []);
          break;
      }
    }

    $designTokenValuePluginManager = $this->createMock(DesignTokenValuePluginManagerInterface::class);
    $designTokenValuePluginManager->expects($this->exactly(5))
      ->method('createInstance')
      ->willReturnCallback(static function (string $type, array $preparedPropertyConfig) use ($properties, $configuration, $instances) {
        foreach ($properties as $property => $propertyType) {
          if ($propertyType != $type) {
            continue;
          }

          $propertyConfig = DesignTokenValuePluginBase::prepareConfiguration($configuration[$property]);
          if ($propertyConfig == $preparedPropertyConfig) {
            return $instances[$property];
          }
        }
        return '';
      });

    $container = new ContainerBuilder();
    $container->set(DesignTokenValuePluginManagerInterface::class, $designTokenValuePluginManager);
    \Drupal::setContainer($container);
  }

}

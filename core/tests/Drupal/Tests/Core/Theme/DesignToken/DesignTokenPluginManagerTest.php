<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\DesignToken;
use Drupal\Core\Theme\DesignToken\DesignTokenPluginManager;
use Drupal\Core\Theme\DesignToken\DesignTokenPluginManagerInterface;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManagerInterface;
use Drupal\Core\Theme\DesignToken\DtcgInterface;
use Drupal\Core\Theme\DesignToken\Group as DesignTokenGroup;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Dimension;
use Drupal\Core\Theme\Plugin\DesignTokenValue\FontFamily;
use Drupal\Core\Theme\Plugin\DesignTokenValue\FontWeight;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Number;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests design token plugin manager.
 */
#[CoversClass(DesignTokenPluginManager::class)]
#[Group('design_token')]
class DesignTokenPluginManagerTest extends UnitTestCase {

  /**
   * The design token plugin manager.
   */
  protected DesignTokenPluginManagerInterface $designTokenPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock all dependencies for DesignTokenPluginManager.
    $moduleHandler = $this->createStub(ModuleHandlerInterface::class);
    $themeHandler = $this->createStub(ThemeHandlerInterface::class);
    $cacheBackend = $this->createStub(CacheBackendInterface::class);
    $designTokenValuePluginManager = $this->createStub(DesignTokenValuePluginManagerInterface::class);

    $valuePlugins = static::getValuePlugins();
    $designTokenValuePluginManager
      ->method('createInstance')
      ->willReturnCallback(static function (string $type, array $config) use ($valuePlugins) {
        if (isset($valuePlugins[$type])) {
          return $valuePlugins[$type];
        }
        throw new PluginException();
      });

    $this->designTokenPluginManager = new DesignTokenPluginManager(
      $moduleHandler,
      $themeHandler,
      $cacheBackend,
      $designTokenValuePluginManager,
    );
  }

  /**
   * Fake Design token value plugin instances.
   *
   * Consistent for assertion results.
   *
   * @return \Drupal\Core\Theme\DesignToken\DesignTokenValueInterface[]
   *   The design token value plugin instances.
   */
  protected static function getValuePlugins(): array {
    $instances = [
      'dimension' => new Dimension(['unit' => 'px', 'value' => 5], 'dimension', []),
      'fontFamily' => new FontFamily(['value' => 'Arial'], 'fontFamily', []),
      'fontWeight' => new FontWeight(['value' => 600], 'fontWeight', []),
      'number' => new Number(['value' => 5], 'number', []),
    ];

    return $instances;
  }

  /**
   * Tests parseDtcg.
   */
  #[DataProvider('providerParseDtcg')]
  public function testParseDtcg(array $data, ?DtcgInterface $expected): void {
    $reflection = new \ReflectionClass($this->designTokenPluginManager);
    $method = $reflection->getMethod('parseDtcg');
    $this->assertEquals($expected, $method->invoke($this->designTokenPluginManager, 'test', 'root name', $data));
  }

  /**
   * Data provider for ::testParseDtcg().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerParseDtcg(): iterable {
    $valuePlugins = static::getValuePlugins();
    yield 'One token' => [
      [
        '$type' => 'number',
        '$value' => 5,
        '$description' => 'my description',
      ],
      new DesignToken(
        provider: 'test',
        name: 'root name',
        value: $valuePlugins['number'],
        description: new TranslatableMarkup('my description'),
        type: 'number',
      ),
    ];

    yield 'One group' => [
      [
        '$type' => 'number',
        '$description' => 'my description',
      ],
      new DesignTokenGroup(
        provider: 'test',
        name: 'root name',
        description: new TranslatableMarkup('my description'),
        type: 'number',
      ),
    ];

    $group = new DesignTokenGroup(
      provider: 'test',
      name: 'root name',
      description: new TranslatableMarkup('my description'),
      type: 'number',
    );
    $token1 = new DesignToken(
      provider: 'test',
      name: 'my token 1',
      value: $valuePlugins['fontWeight'],
      parent: $group,
      description: new TranslatableMarkup('my description 1'),
      type: 'fontWeight',
    );
    $token2 = new DesignToken(
      provider: 'test',
      name: 'my token 2',
      value: $valuePlugins['fontFamily'],
      parent: $group,
      description: new TranslatableMarkup('my description 2'),
      type: 'fontFamily',
    );
    $group->setChildren([
      'my token 1' => $token1,
      'my token 2' => $token2,
    ]);

    yield 'One group with 2 tokens' => [
      [
        '$type' => 'number',
        '$description' => 'my description',
        'my token 1' => [
          '$type' => 'fontWeight',
          '$value' => 600,
          '$description' => 'my description 1',
        ],
        'my token 2' => [
          '$type' => 'fontFamily',
          '$value' => 'Arial',
          '$description' => 'my description 2',
        ],
      ],
      $group,
    ];

    yield 'Invalid token' => [
      [
        '$type' => 'foo',
        '$value' => 5,
        '$description' => 'invalid token',
      ],
      NULL,
    ];

    yield 'One group with 2 tokens and one invalid' => [
      [
        '$type' => 'number',
        '$description' => 'my description',
        'my token 1' => [
          '$type' => 'fontWeight',
          '$value' => 600,
          '$description' => 'my description 1',
        ],
        'my token 2' => [
          '$type' => 'fontFamily',
          '$value' => 'Arial',
          '$description' => 'my description 2',
        ],
        'my token 3' => [
          '$type' => 'foo',
          '$value' => 5,
          '$description' => 'invalid token',
        ],
      ],
      $group,
    ];

    $group1 = new DesignTokenGroup(
      provider: 'test',
      name: 'root name',
      description: new translatableMarkup('my description'),
      type: 'number',
    );
    $group2 = new DesignTokenGroup(
      provider: 'test',
      name: 'sub group',
      parent: $group1,
      description: new translatableMarkup('my description 2'),
      type: 'number',
    );
    $token1 = new DesignToken(
      provider: 'test',
      name: 'my token 1',
      value: $valuePlugins['fontWeight'],
      parent: $group1,
      description: new translatableMarkup('my description 1'),
      type: 'fontWeight',
    );
    $token2 = new DesignToken(
      provider: 'test',
      name: 'my token 2',
      value: $valuePlugins['fontFamily'],
      parent: $group2,
      description: new translatableMarkup('my description 2'),
      type: 'fontFamily',
    );
    $group1->setChildren([
      'my token 1' => $token1,
      'sub group' => $group2,
    ]);
    $group2->setChildren([
      'my token 2' => $token2,
    ]);

    yield 'One group with one token and one group with one token' => [
      [
        '$type' => 'number',
        '$description' => 'my description',
        'my token 1' => [
          '$type' => 'fontWeight',
          '$value' => 600,
          '$description' => 'my description 1',
        ],
        'sub group' => [
          '$description' => 'my description 2',
          'my token 2' => [
            '$type' => 'fontFamily',
            '$value' => 'Arial',
            '$description' => 'my description 2',
          ],
        ],
      ],
      $group1,
    ];
  }

  /**
   * Tests flattenDefinitions.
   */
  #[DataProvider('providerFlattenDefinitions')]
  public function testFlattenDefinitions(?DtcgInterface $data, array $expected): void {
    $reflection = new \ReflectionClass($this->designTokenPluginManager);
    $method = $reflection->getMethod('flattenDefinitions');
    $this->assertEquals($expected, $method->invoke($this->designTokenPluginManager, $data));
  }

  /**
   * Data provider for ::testFlattenDefinitions().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerFlattenDefinitions(): iterable {
    $valuePlugins = static::getValuePlugins();
    yield 'Null value' => [
      NULL,
      [],
    ];

    $token = new DesignToken(
      provider: 'test',
      name: 'root name',
      value: $valuePlugins['number'],
      description: new translatableMarkup('my description'),
      type: 'number',
    );
    yield 'One token' => [
      $token,
      [
        'root_name' => $token,
      ],
    ];

    yield 'One empty group' => [
      new DesignTokenGroup(
        provider: 'test',
        name: 'root name',
        description: new translatableMarkup('my description'),
        type: 'number',
      ),
      [],
    ];

    $group = new DesignTokenGroup(
      provider: 'test',
      name: 'root name',
      description: new translatableMarkup('my description'),
      type: 'number',
    );
    $token1 = new DesignToken(
      provider: 'test',
      name: 'my token 1',
      value: $valuePlugins['fontWeight'],
      parent: $group,
      description: new translatableMarkup('my description 1'),
      type: 'fontWeight',
    );
    $token2 = new DesignToken(
      provider: 'test',
      name: 'my token 2',
      value: $valuePlugins['fontFamily'],
      parent: $group,
      description: new translatableMarkup('my description 2'),
      type: 'fontFamily',
    );
    $group->setChildren([
      'my token 1' => $token1,
      'my token 2' => $token2,
    ]);

    yield 'One group with 2 tokens' => [
      $group,
      [
        'root_name.my_token_1' => $token1,
        'root_name.my_token_2' => $token2,
      ],
    ];

    $group1 = new DesignTokenGroup(
      provider: 'test',
      name: 'root name',
      description: new translatableMarkup('my description'),
      type: 'number',
    );
    $group2 = new DesignTokenGroup(
      provider: 'test',
      name: 'sub group',
      parent: $group1,
      description: new translatableMarkup('my description 2'),
      type: 'number',
    );
    $token1 = new DesignToken(
      provider: 'test',
      name: 'my token 1',
      value: $valuePlugins['fontWeight'],
      parent: $group1,
      description: new translatableMarkup('my description 1'),
      type: 'fontWeight',
    );
    $token2 = new DesignToken(
      provider: 'test',
      name: 'my token 2',
      value: $valuePlugins['fontFamily'],
      parent: $group2,
      description: new translatableMarkup('my description 2'),
      type: 'fontFamily',
    );
    $group1->setChildren([
      'my token 1' => $token1,
      'sub group' => $group2,
    ]);
    $group2->setChildren([
      'my token 2' => $token2,
    ]);

    yield 'One group with one token and one group with one token' => [
      $group1,
      [
        'root_name.my_token_1' => $token1,
        'root_name.sub_group.my_token_2' => $token2,
      ],
    ];
  }

  /**
   * Tests extractDefinitions.
   */
  #[DataProvider('providerExtractDefinitions')]
  public function testExtractDefinitions(array $discovered, array $expected): void {
    $reflection = new \ReflectionClass($this->designTokenPluginManager);
    $method = $reflection->getMethod('extractDefinitions');
    $this->assertEquals($expected, $method->invoke($this->designTokenPluginManager, $discovered));
  }

  /**
   * Data provider for ::testExtractDefinitions().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerExtractDefinitions(): iterable {
    $valuePlugins = static::getValuePlugins();
    yield 'None discovered' => [
      [],
      [],
    ];

    yield 'Invalid token' => [
      [
        'id 1' => [
          'id' => 'id 1',
          'provider' => 'test',
          '$type' => 'foo',
          '$value' => 5,
          '$description' => 'invalid token',
        ],
      ],
      [],
    ];

    yield 'One token' => [
      [
        'id 1' => [
          'id' => 'id 1',
          'provider' => 'test',
          '$type' => 'number',
          '$value' => 5,
          '$description' => 'my description',
        ],
      ],
      [
        'id_1' => new DesignToken(
          provider: 'test',
          name: 'id 1',
          value: $valuePlugins['number'],
          description: new translatableMarkup('my description'),
          type: 'number',
        ),
      ],
    ];

    yield 'One empty group' => [
      [
        'id 1' => [
          'id' => 'id 1',
          'provider' => 'test',
          '$type' => 'number',
          '$description' => 'my description',
        ],
      ],
      [],
    ];

    $group = new DesignTokenGroup(
      provider: 'test',
      name: 'root name',
      description: new translatableMarkup('my description'),
      type: 'number',
    );
    $token1 = new DesignToken(
      provider: 'test',
      name: 'my token 1',
      value: $valuePlugins['fontWeight'],
      parent: $group,
      description: new translatableMarkup('my description 1'),
      type: 'fontWeight',
    );
    $token2 = new DesignToken(
      provider: 'test',
      name: 'my token 2',
      value: $valuePlugins['fontFamily'],
      parent: $group,
      description: new translatableMarkup('my description 2'),
      type: 'fontFamily',
    );
    $group->setChildren([
      'my token 1' => $token1,
      'my token 2' => $token2,
    ]);

    yield 'One group with 2 tokens' => [
      [
        'root name' => [
          'id' => 'root name',
          'provider' => 'test',
          '$type' => 'number',
          '$description' => 'my description',
          'my token 1' => [
            '$type' => 'fontWeight',
            '$value' => 600,
            '$description' => 'my description 1',
          ],
          'my token 2' => [
            '$type' => 'fontFamily',
            '$value' => 'Arial',
            '$description' => 'my description 2',
          ],
        ],
      ],
      [
        'root_name.my_token_1' => $token1,
        'root_name.my_token_2' => $token2,
      ],
    ];

    yield 'One group with 2 tokens and one invalid' => [
      [
        'root name' => [
          'id' => 'root name',
          'provider' => 'test',
          '$type' => 'number',
          '$description' => 'my description',
          'my token 1' => [
            '$type' => 'fontWeight',
            '$value' => 600,
            '$description' => 'my description 1',
          ],
          'my token 2' => [
            '$type' => 'fontFamily',
            '$value' => 'Arial',
            '$description' => 'my description 2',
          ],
          'my token 3' => [
            '$type' => 'foo',
            '$value' => 5,
            '$description' => 'invalid token',
          ],
        ],
      ],
      [
        'root_name.my_token_1' => $token1,
        'root_name.my_token_2' => $token2,
      ],
    ];

    $group1 = new DesignTokenGroup(
      provider: 'test',
      name: 'root name',
      description: new translatableMarkup('my description'),
      type: 'number',
    );
    $group2 = new DesignTokenGroup(
      provider: 'test',
      name: 'sub group',
      parent: $group1,
      description: new translatableMarkup('my description 2'),
      type: 'number',
    );
    $token1 = new DesignToken(
      provider: 'test',
      name: 'my token 1',
      value: $valuePlugins['fontWeight'],
      parent: $group1,
      description: new translatableMarkup('my description 1'),
      type: 'fontWeight',
    );
    $token2 = new DesignToken(
      provider: 'test',
      name: 'my token 2',
      value: $valuePlugins['fontFamily'],
      parent: $group2,
      description: new translatableMarkup('my description 2'),
      type: 'fontFamily',
    );
    $group1->setChildren([
      'my token 1' => $token1,
      'sub group' => $group2,
    ]);
    $group2->setChildren([
      'my token 2' => $token2,
    ]);

    yield 'One group with one token and one group with one token' => [
      [
        'root name' => [
          'id' => 'root name',
          'provider' => 'test',
          '$type' => 'number',
          '$description' => 'my description',
          'my token 1' => [
            '$type' => 'fontWeight',
            '$value' => 600,
            '$description' => 'my description 1',
          ],
          'sub group' => [
            '$description' => 'my description 2',
            'my token 2' => [
              '$type' => 'fontFamily',
              '$value' => 'Arial',
              '$description' => 'my description 2',
            ],
          ],
        ],
      ],
      [
        'root_name.my_token_1' => $token1,
        'root_name.sub_group.my_token_2' => $token2,
      ],
    ];
  }

}

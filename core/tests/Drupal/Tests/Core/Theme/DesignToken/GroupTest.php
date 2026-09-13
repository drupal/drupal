<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\DesignToken;
use Drupal\Core\Theme\DesignToken\Group as DesignTokenGroup;
use Drupal\Core\Theme\Plugin\DesignTokenValue\FontFamily;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Number;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests design token group object.
 */
#[CoversClass(DesignTokenGroup::class)]
#[Group('design_token')]
class GroupTest extends UnitTestCase {

  /**
   * Tests getType.
   */
  #[DataProvider('providerGetType')]
  public function testGetType(?string $type, ?DesignTokenGroup $parent, ?string $expectedType): void {
    $group = new DesignTokenGroup(
      provider: 'test',
      name: 'test',
      parent: $parent,
      type: $type,
    );
    $this->assertEquals($expectedType, $group->getType());
  }

  /**
   * Data provider for ::testGetType().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerGetType(): iterable {
    yield 'No type' => [
      NULL,
      NULL,
      NULL,
    ];

    yield 'With type' => [
      'myType',
      NULL,
      'myType',
    ];

    yield 'From parent' => [
      NULL,
      new DesignTokenGroup('test', 'test2', type: 'parentType'),
      'parentType',
    ];

    yield 'With type and parent' => [
      'myType',
      new DesignTokenGroup('test', 'test2', type: 'parentType'),
      'myType',
    ];
  }

  /**
   * Tests getPath.
   */
  #[DataProvider('providerGetPath')]
  public function testGetPath(string $name, ?DesignTokenGroup $parent, ?string $expectedType): void {
    $group = new DesignTokenGroup(
      provider: 'test',
      name: $name,
      parent: $parent,
    );
    $this->assertEquals($expectedType, $group->getPath());
  }

  /**
   * Data provider for ::testGetPath().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerGetPath(): iterable {
    yield 'Root' => [
      'myPath',
      NULL,
      'myPath',
    ];

    yield 'With parent' => [
      'myPath',
      new DesignTokenGroup('test', 'parentName'),
      'parentName.myPath',
    ];

    yield 'With parent with parent' => [
      'myPath',
      new DesignTokenGroup('test', 'parentName', parent: new DesignTokenGroup('test', 'parentName2')),
      'parentName2.parentName.myPath',
    ];
  }

  /**
   * Tests toDtcg.
   */
  #[DataProvider('providerToDtcg')]
  public function testToDtcg(?string $type, ?string $description, array $children, array $expected): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $description = $description ? new TranslatableMarkup($description) : NULL;

    $group = new DesignTokenGroup(
      provider: 'test',
      name: 'test',
      children: $children,
      description: $description,
      type: $type,
    );
    $this->assertEquals($expected, $group->toDtcg());
  }

  /**
   * Data provider for ::testToDtcg().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToDtcg(): iterable {
    yield 'Empty' => [
      NULL,
      NULL,
      [],
      [],
    ];

    yield 'With type' => [
      'myType',
      NULL,
      [],
      [
        '$type' => 'myType',
      ],
    ];

    yield 'With description' => [
      NULL,
      'my description',
      [],
      [
        '$description' => 'my description',
      ],
    ];

    yield 'With children' => [
      NULL,
      NULL,
      [
        'group' => new DesignTokenGroup(
          provider: 'test',
          name: 'group',
          children: [
            'number' => new DesignToken(
              provider: 'test',
              name: 'number',
              value: new Number(['value' => 5], 'number', []),
              type: 'number',
            ),
          ],
          description: new TranslatableMarkup('my description'),
        ),
        'my-font-family' => new DesignToken(
          provider: 'test',
          name: 'my-font-family',
          value: new FontFamily(['value' => 'Arial'], 'fontFamily', []),
          type: 'fontFamily',
        ),
      ],
      [
        'group' => [
          '$description' => 'my description',
          'number' => [
            '$type' => 'number',
            '$value' => 5,
          ],
        ],
        'my-font-family' => [
          '$type' => 'fontFamily',
          '$value' => ['Arial'],
        ],
      ],
    ];
  }

}

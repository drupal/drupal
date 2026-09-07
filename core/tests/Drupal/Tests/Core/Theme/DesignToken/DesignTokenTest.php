<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\DesignToken;
use Drupal\Core\Theme\DesignToken\DesignTokenValueInterface;
use Drupal\Core\Theme\DesignToken\Group as DesignTokenGroup;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Number;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests design token object.
 */
#[CoversClass(DesignToken::class)]
#[Group('design_token')]
class DesignTokenTest extends UnitTestCase {

  /**
   * Tests getType.
   */
  #[DataProvider('providerGetType')]
  public function testGetType(?string $type, ?DesignTokenGroup $parent, ?string $expectedType): void {
    $value = new Number(['value' => 5], 'number', []);
    $token = new DesignToken(
      provider: 'test',
      name: 'test',
      value: $value,
      parent: $parent,
      type: $type,
    );
    $this->assertEquals($expectedType, $token->getType());
  }

  /**
   * Data provider for ::testGetType().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerGetType(): iterable {
    // This case is not supposed to happen on real usage.
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
    $value = new Number(['value' => 5], 'number', []);
    $token = new DesignToken(
      provider: 'test',
      name: $name,
      value: $value,
      parent: $parent,
    );
    $this->assertEquals($expectedType, $token->getPath());
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
  public function testToDtcg(?string $type, DesignTokenValueInterface $value, ?string $description, array $expected): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $description = $description ? new TranslatableMarkup($description) : NULL;

    $token = new DesignToken(
      provider: 'test',
      name: 'test',
      value: $value,
      description: $description,
      type: $type,
    );
    $this->assertEquals($expected, $token->toDtcg());
  }

  /**
   * Data provider for ::testToDtcg().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerToDtcg(): iterable {
    $value = new Number(['value' => 5], 'number', []);
    yield 'With type' => [
      'myType',
      $value,
      NULL,
      [
        '$value' => 5,
        '$type' => 'myType',
      ],
    ];

    yield 'With description' => [
      NULL,
      $value,
      'my description',
      [
        '$type' => NULL,
        '$value' => 5,
        '$description' => 'my description',
      ],
    ];
  }

}

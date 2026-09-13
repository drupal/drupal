<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\DesignToken;

use Drupal\Core\Theme\Entity\DesignToken;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests design token entity.
 */
#[CoversClass(DesignToken::class)]
#[Group('design_token')]
class DesignTokenEntityTest extends UnitTestCase {

  /**
   * Tests getCssScopeName.
   */
  #[DataProvider('providerGetCssScopeName')]
  public function testGetCssScopeName(string $scope, string $expected): void {
    $this->assertEquals($expected, DesignToken::getCssScopeName($scope));
  }

  /**
   * Data provider for ::testGetCssScopeName().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerGetCssScopeName(): iterable {
    yield 'Nothing to replace' => [
      'my-scope',
      'my-scope',
    ];

    yield 'One character to replace' => [
      '%my-scope',
      '.my-scope',
    ];

    yield 'Multiple characters to replace' => [
      '%my-scope %more-precise %even%more%precise',
      '.my-scope .more-precise .even.more.precise',
    ];
  }

  /**
   * Tests getConfigScopeName.
   */
  #[DataProvider('providerGetConfigScopeName')]
  public function testGetConfigScopeName(string $scope, string $expected): void {
    $this->assertEquals($expected, DesignToken::getConfigScopeName($scope));
  }

  /**
   * Data provider for ::testGetConfigScopeName().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerGetConfigScopeName(): iterable {
    yield 'Nothing to replace' => [
      'my-scope',
      'my-scope',
    ];

    yield 'One character to replace' => [
      '.my-scope',
      '%my-scope',
    ];

    yield 'Multiple characters to replace' => [
      '.my-scope .more-precise .even.more.precise',
      '%my-scope %more-precise %even%more%precise',
    ];
  }

  /**
   * Tests getCssVariableName.
   */
  #[DataProvider('providerGetCssVariableName')]
  public function testGetCssVariableName(string $path, string $expected): void {
    $token = new DesignToken(['path' => $path], 'design_token');
    $this->assertEquals($expected, $token->getCssVariableName());
  }

  /**
   * Data provider for ::testGetCssVariableName().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerGetCssVariableName(): iterable {
    yield 'Simple' => [
      'myPath',
      '--myPath',
    ];

    yield 'With underscores' => [
      'my_token_path',
      '--my-token-path',
    ];

    yield 'With spaces' => [
      'my token path',
      '--my-token-path',
    ];

    yield 'With dots' => [
      'my.token.path',
      '--my-token-path',
    ];
  }

}

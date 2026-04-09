<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\DependencyInjection\Container as DrupalContainer;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\DependencyInjection\Container;

// cspell:ignore cnenzrgre
/**
 * Tests Drupal\Core\Cache\Context\CacheContextsManager.
 */
#[CoversClass(CacheContextsManager::class)]
#[Group('Cache')]
class CacheContextsManagerTest extends UnitTestCase {

  /**
   * Tests optimize tokens.
   */
  #[DataProvider('providerTestOptimizeTokens')]
  public function testOptimizeTokens(array $context_tokens, array $optimized_context_tokens, int $expected_container_calls): void {
    $container = $this->createMock(DrupalContainer::class);
    $container->expects($this->exactly($expected_container_calls))
      ->method('get')
      ->willReturnCallback(fn($service_id) => match ($service_id) {
        'cache_context.a' => new FooCacheContext(),
        'cache_context.a.b' => new FooCacheContext(),
        'cache_context.a.b.c' => new BazCacheContext(),
        'cache_context.x' => new BazCacheContext(),
        'cache_context.a.b.no-optimize' => new NoOptimizeCacheContext(),
      });
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());

    $this->assertSame($optimized_context_tokens, $cache_contexts_manager->optimizeTokens($context_tokens));
  }

  /**
   * Provides a list of context token sets.
   */
  public static function providerTestOptimizeTokens(): array {
    return [
      // No ancestors found, 0 container calls needed.
      [['a', 'x'], ['a', 'x'], 0],
      [['a.b', 'x'], ['a.b', 'x'], 0],

      // Direct ancestor, single-level hierarchy: 1 call to check max-age.
      [['a', 'a.b'], ['a'], 1],
      [['a.b', 'a'], ['a'], 1],

      // Direct ancestor, multi-level hierarchy: 1 call to check max-age.
      [['a.b', 'a.b.c'], ['a.b'], 1],
      [['a.b.c', 'a.b'], ['a.b'], 1],

      // Indirect ancestor: 1 call to check max-age.
      [['a', 'a.b.c'], ['a'], 1],
      [['a.b.c', 'a'], ['a'], 1],

      // Direct & indirect ancestors: 2 calls (one for each descendant).
      [['a', 'a.b', 'a.b.c'], ['a'], 2],
      [['a', 'a.b.c', 'a.b'], ['a'], 2],
      [['a.b', 'a', 'a.b.c'], ['a'], 2],
      [['a.b', 'a.b.c', 'a'], ['a'], 2],
      [['a.b.c', 'a.b', 'a'], ['a'], 2],
      [['a.b.c', 'a', 'a.b'], ['a'], 2],

      // Using parameters: 1 call to check max-age.
      [['a', 'a.b.c:foo'], ['a'], 1],
      [['a.b.c:foo', 'a'], ['a'], 1],
      [['a.b.c:foo', 'a.b.c'], ['a.b.c'], 1],

      // max-age 0 is treated as non-optimizable: 2 calls (both have ancestors).
      [['a.b.no-optimize', 'a.b', 'a'], ['a.b.no-optimize', 'a'], 2],
    ];
  }

  /**
   * Tests convert tokens to keys.
   */
  public function testConvertTokensToKeys(): void {
    $container = $this->getMockContainer();
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());

    $new_keys = $cache_contexts_manager->convertTokensToKeys([
      'foo',
      'baz:parameterA',
      'baz:parameterB',
    ]);

    $expected = [
      '[baz:parameterA]=cnenzrgreN',
      '[baz:parameterB]=cnenzrgreO',
      '[foo]=bar',
    ];
    $this->assertEquals($expected, $new_keys->getKeys());
  }

  /**
   * Tests invalid context.
   *
   * @legacy-covers ::convertTokensToKeys
   */
  public function testInvalidContext(): void {
    $container = $this->getMockContainer();
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());

    $this->expectException(\AssertionError::class);
    $cache_contexts_manager->convertTokensToKeys(["non-cache-context"]);
  }

  /**
   * Tests invalid calculated context.
   *
   * @legacy-covers ::convertTokensToKeys
   */
  #[DataProvider('providerTestInvalidCalculatedContext')]
  public function testInvalidCalculatedContext(string $context_token): void {
    $container = $this->getMockContainer();
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());

    $this->expectException(\Exception::class);
    $cache_contexts_manager->convertTokensToKeys([$context_token]);
  }

  /**
   * Provides a list of invalid 'baz' cache contexts: the parameter is missing.
   */
  public static function providerTestInvalidCalculatedContext(): array {
    return [
      ['baz'],
      ['baz:'],
    ];
  }

  public function testAvailableContextStrings(): void {
    $cache_contexts_manager = new CacheContextsManager($this->getMockContainer(), $this->getContextsFixture());
    $contexts = $cache_contexts_manager->getAll();
    $this->assertEquals(["foo", "baz"], $contexts);
  }

  public function testAvailableContextLabels(): void {
    $container = $this->getMockContainer();
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());
    $labels = $cache_contexts_manager->getLabels();
    $expected = ["foo" => "Foo"];
    $this->assertEquals($expected, $labels);
  }

  protected function getContextsFixture(): array {
    return ['foo', 'baz'];
  }

  protected function getMockContainer(): Stub {
    $container = $this->createStub(DrupalContainer::class);
    $container
      ->method('get')
      ->willReturnMap([
        [
          'cache_context.foo',
          Container::EXCEPTION_ON_INVALID_REFERENCE,
          new FooCacheContext(),
        ],
        [
          'cache_context.baz',
          Container::EXCEPTION_ON_INVALID_REFERENCE,
          new BazCacheContext(),
        ],
      ]);
    return $container;
  }

  /**
   * Provides a list of cache context token arrays.
   *
   * @return array
   *   An array of cache context token arrays.
   */
  public static function validateTokensProvider(): array {
    return [
      [[], FALSE],
      [['foo'], FALSE],
      [['foo', 'foo.bar'], FALSE],
      [['foo', 'baz:llama'], FALSE],
      // Invalid.
      [[FALSE], 'Cache contexts must be strings, boolean given.'],
      [[TRUE], 'Cache contexts must be strings, boolean given.'],
      [['foo', FALSE], 'Cache contexts must be strings, boolean given.'],
      [[NULL], 'Cache contexts must be strings, NULL given.'],
      [['foo', NULL], 'Cache contexts must be strings, NULL given.'],
      [[1337], 'Cache contexts must be strings, integer given.'],
      [['foo', 1337], 'Cache contexts must be strings, integer given.'],
      [[3.14], 'Cache contexts must be strings, double given.'],
      [['foo', 3.14], 'Cache contexts must be strings, double given.'],
      [[[]], 'Cache contexts must be strings, array given.'],
      [['foo', []], 'Cache contexts must be strings, array given.'],
      [['foo', ['bar']], 'Cache contexts must be strings, array given.'],
      [[new \stdClass()], 'Cache contexts must be strings, object given.'],
      [['foo', new \stdClass()], 'Cache contexts must be strings, object given.'],
      // Non-existing.
      [['foo.bar', 'qux'], '"qux" is not a valid cache context ID.'],
      [['qux', 'baz'], '"qux" is not a valid cache context ID.'],
    ];
  }

  /**
   * Tests validate contexts.
   *
   * @legacy-covers ::validateTokens
   */
  #[DataProvider('validateTokensProvider')]
  public function testValidateContexts(array $contexts, bool|string $expected_exception_message): void {
    $container = new ContainerBuilder();
    $cache_contexts_manager = new CacheContextsManager($container, ['foo', 'foo.bar', 'baz']);
    if ($expected_exception_message !== FALSE) {
      $this->expectException('LogicException');
      $this->expectExceptionMessage($expected_exception_message);
    }
    // If it doesn't throw an exception, validateTokens() returns NULL.
    $this->assertNull($cache_contexts_manager->validateTokens($contexts));
  }

}

/**
 * Fake cache context class.
 */
class FooCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return 'Foo';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): string {
    return 'bar';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    return new CacheableMetadata();
  }

}

/**
 * Fake calculated cache context class.
 */
class BazCacheContext implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return 'Baz';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = NULL): string {
    if (!is_string($parameter) || strlen($parameter) === 0) {
      throw new \Exception();
    }
    return str_rot13($parameter);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($parameter = NULL): CacheableMetadata {
    return new CacheableMetadata();
  }

}

/**
 * Non-optimizable context class.
 */
class NoOptimizeCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel(): string {
    return 'Foo';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(): string {
    return 'bar';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();
    return $cacheable_metadata->setCacheMaxAge(0);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

// cspell:ignore cnenzrgre

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\CacheContextsManager
 * @group Cache
 */
class CacheContextsManagerTest extends UnitTestCase {

  /**
   * @covers ::optimizeTokens
   *
   * @dataProvider providerTestOptimizeTokens
   */
  public function testOptimizeTokens(array $context_tokens, array $optimized_context_tokens) {
    $container = $this->getMockBuilder('Drupal\Core\DependencyInjection\Container')
      ->disableOriginalConstructor()
      ->getMock();
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap([
        [
          'cache_context.a',
          Container::EXCEPTION_ON_INVALID_REFERENCE,
          new FooCacheContext(),
        ],
        [
          'cache_context.a.b',
          Container::EXCEPTION_ON_INVALID_REFERENCE,
          new FooCacheContext(),
        ],
        [
          'cache_context.a.b.c',
          Container::EXCEPTION_ON_INVALID_REFERENCE,
          new BazCacheContext(),
        ],
        [
          'cache_context.x',
          Container::EXCEPTION_ON_INVALID_REFERENCE,
          new BazCacheContext(),
        ],
        [
          'cache_context.a.b.no-optimize',
          Container::EXCEPTION_ON_INVALID_REFERENCE,
          new NoOptimizeCacheContext(),
        ],
      ]);
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());

    $this->assertSame($optimized_context_tokens, $cache_contexts_manager->optimizeTokens($context_tokens));
  }

  /**
   * Provides a list of context token sets.
   */
  public function providerTestOptimizeTokens() {
    return [
      [['a', 'x'], ['a', 'x']],
      [['a.b', 'x'], ['a.b', 'x']],

      // Direct ancestor, single-level hierarchy.
      [['a', 'a.b'], ['a']],
      [['a.b', 'a'], ['a']],

      // Direct ancestor, multi-level hierarchy.
      [['a.b', 'a.b.c'], ['a.b']],
      [['a.b.c', 'a.b'], ['a.b']],

      // Indirect ancestor.
      [['a', 'a.b.c'], ['a']],
      [['a.b.c', 'a'], ['a']],

      // Direct & indirect ancestors.
      [['a', 'a.b', 'a.b.c'], ['a']],
      [['a', 'a.b.c', 'a.b'], ['a']],
      [['a.b', 'a', 'a.b.c'], ['a']],
      [['a.b', 'a.b.c', 'a'], ['a']],
      [['a.b.c', 'a.b', 'a'], ['a']],
      [['a.b.c', 'a', 'a.b'], ['a']],

      // Using parameters.
      [['a', 'a.b.c:foo'], ['a']],
      [['a.b.c:foo', 'a'], ['a']],
      [['a.b.c:foo', 'a.b.c'], ['a.b.c']],

      // max-age 0 is treated as non-optimizable.
      [['a.b.no-optimize', 'a.b', 'a'], ['a.b.no-optimize', 'a']],
    ];
  }

  /**
   * @covers ::convertTokensToKeys
   */
  public function testConvertTokensToKeys() {
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
   * @covers ::convertTokensToKeys
   */
  public function testInvalidContext() {
    $container = $this->getMockContainer();
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());

    $this->expectException(\AssertionError::class);
    $cache_contexts_manager->convertTokensToKeys(["non-cache-context"]);
  }

  /**
   * @covers ::convertTokensToKeys
   *
   * @dataProvider providerTestInvalidCalculatedContext
   */
  public function testInvalidCalculatedContext($context_token) {
    $container = $this->getMockContainer();
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());

    $this->expectException(\Exception::class);
    $cache_contexts_manager->convertTokensToKeys([$context_token]);
  }

  /**
   * Provides a list of invalid 'baz' cache contexts: the parameter is missing.
   */
  public function providerTestInvalidCalculatedContext() {
    return [
      ['baz'],
      ['baz:'],
    ];
  }

  public function testAvailableContextStrings() {
    $cache_contexts_manager = new CacheContextsManager($this->getMockContainer(), $this->getContextsFixture());
    $contexts = $cache_contexts_manager->getAll();
    $this->assertEquals(["foo", "baz"], $contexts);
  }

  public function testAvailableContextLabels() {
    $container = $this->getMockContainer();
    $cache_contexts_manager = new CacheContextsManager($container, $this->getContextsFixture());
    $labels = $cache_contexts_manager->getLabels();
    $expected = ["foo" => "Foo"];
    $this->assertEquals($expected, $labels);
  }

  protected function getContextsFixture() {
    return ['foo', 'baz'];
  }

  protected function getMockContainer() {
    $container = $this->getMockBuilder('Drupal\Core\DependencyInjection\Container')
      ->disableOriginalConstructor()
      ->getMock();
    $container->expects($this->any())
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
   */
  public function validateTokensProvider() {
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
   * @covers ::validateTokens
   *
   * @dataProvider validateTokensProvider
   */
  public function testValidateContexts(array $contexts, $expected_exception_message) {
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
  public static function getLabel() {
    return 'Foo';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return 'bar';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
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
  public static function getLabel() {
    return 'Baz';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = NULL) {
    if (!is_string($parameter) || strlen($parameter) === 0) {
      throw new \Exception();
    }
    return str_rot13($parameter);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($parameter = NULL) {
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
  public static function getLabel() {
    return 'Foo';
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
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

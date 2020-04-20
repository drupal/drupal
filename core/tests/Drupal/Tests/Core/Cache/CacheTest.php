<?php

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Cache
 * @group Cache
 */
class CacheTest extends UnitTestCase {

  /**
   * Provides a list of cache tags arrays.
   *
   * @return array
   */
  public function validateTagsProvider() {
    return [
      [[], FALSE],
      [['foo'], FALSE],
      [['foo', 'bar'], FALSE],
      [['foo', 'bar', 'llama:2001988', 'baz', 'llama:14031991'], FALSE],
      // Invalid.
      [[FALSE], 'Cache tags must be strings, boolean given.'],
      [[TRUE], 'Cache tags must be strings, boolean given.'],
      [['foo', FALSE], 'Cache tags must be strings, boolean given.'],
      [[NULL], 'Cache tags must be strings, NULL given.'],
      [['foo', NULL], 'Cache tags must be strings, NULL given.'],
      [[1337], 'Cache tags must be strings, integer given.'],
      [['foo', 1337], 'Cache tags must be strings, integer given.'],
      [[3.14], 'Cache tags must be strings, double given.'],
      [['foo', 3.14], 'Cache tags must be strings, double given.'],
      [[[]], 'Cache tags must be strings, array given.'],
      [['foo', []], 'Cache tags must be strings, array given.'],
      [['foo', ['bar']], 'Cache tags must be strings, array given.'],
      [[new \stdClass()], 'Cache tags must be strings, object given.'],
      [['foo', new \stdClass()], 'Cache tags must be strings, object given.'],
    ];
  }

  /**
   * Provides a list of pairs of cache tags arrays to be merged.
   *
   * @return array
   */
  public function mergeTagsProvider() {
    return [
      [[], [], []],
      [['bar', 'foo'], ['bar'], ['foo']],
      [['bar', 'foo'], ['foo'], ['bar']],
      [['bar', 'foo'], ['foo'], ['bar', 'foo']],
      [['bar', 'foo'], ['foo'], ['foo', 'bar']],
      [['bar', 'foo'], ['bar', 'foo'], ['foo', 'bar']],
      [['bar', 'foo'], ['foo', 'bar'], ['foo', 'bar']],
      [['bar', 'foo', 'llama'], ['bar', 'foo'], ['foo', 'bar'], ['llama', 'foo']],
    ];
  }

  /**
   * @covers ::mergeTags
   *
   * @dataProvider mergeTagsProvider
   */
  public function testMergeTags(array $expected, ...$cache_tags) {
    $this->assertEquals($expected, Cache::mergeTags(...$cache_tags));
  }

  /**
   * Provides a list of pairs of cache tags arrays to be merged.
   *
   * @return array
   */
  public function mergeMaxAgesProvider() {
    return [
      [Cache::PERMANENT, Cache::PERMANENT, Cache::PERMANENT],
      [60, 60, 60],
      [0, 0, 0],

      [0, 60, 0],
      [0, 0, 60],

      [0, Cache::PERMANENT, 0],
      [0, 0, Cache::PERMANENT],

      [60, Cache::PERMANENT, 60],
      [60, 60, Cache::PERMANENT],

      [Cache::PERMANENT, Cache::PERMANENT, Cache::PERMANENT, Cache::PERMANENT],
      [30, 60, 60, 30],
      [0, 0, 0, 0],

      [60, Cache::PERMANENT, 60, Cache::PERMANENT],
      [60, 60, Cache::PERMANENT, Cache::PERMANENT],
      [60, Cache::PERMANENT, Cache::PERMANENT, 60],
    ];
  }

  /**
   * @covers ::mergeMaxAges
   *
   * @dataProvider mergeMaxAgesProvider
   */
  public function testMergeMaxAges($expected, ...$max_ages) {
    $this->assertSame($expected, Cache::mergeMaxAges(...$max_ages));
  }

  /**
   * Provides a list of pairs of cache contexts arrays to be merged.
   *
   * @return array
   */
  public function mergeCacheContextsProvide() {
    return [
      [[], [], []],
      [['foo'], [], ['foo']],
      [['foo'], ['foo'], []],

      [['bar', 'foo'], ['foo'], ['bar']],
      [['bar', 'foo'], ['bar'], ['foo']],

      [['foo.bar', 'foo.foo'], ['foo.foo'], ['foo.bar']],
      [['foo.bar', 'foo.foo'], ['foo.bar'], ['foo.foo']],

      [['bar', 'foo', 'llama'], [], ['foo', 'bar', 'llama']],
      [['bar', 'foo', 'llama'], ['foo', 'bar', 'llama'], []],

      [['bar', 'foo', 'llama'], ['foo'], ['foo', 'bar', 'llama']],
      [['bar', 'foo', 'llama'], ['foo', 'bar', 'llama'], ['foo']],
    ];
  }

  /**
   * @covers ::mergeContexts
   *
   * @dataProvider mergeCacheContextsProvide
   */
  public function testMergeCacheContexts(array $expected, ...$contexts) {
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);
    $container = $this->prophesize(Container::class);
    $container->get('cache_contexts_manager')->willReturn($cache_contexts_manager->reveal());
    \Drupal::setContainer($container->reveal());
    $this->assertSame($expected, Cache::mergeContexts(...$contexts));
  }

  /**
   * Provides a list of pairs of (prefix, suffixes) to build cache tags from.
   *
   * @return array
   */
  public function buildTagsProvider() {
    return [
      ['node', [1], ['node:1']],
      ['node', [1, 2, 3], ['node:1', 'node:2', 'node:3']],
      ['node', [3, 2, 1], ['node:3', 'node:2', 'node:1']],
      ['node', [NULL], ['node:']],
      ['node', [TRUE, FALSE], ['node:1', 'node:']],
      ['node', ['a', 'z', 'b'], ['node:a', 'node:z', 'node:b']],
      // No suffixes, no cache tags.
      ['', [], []],
      ['node', [], []],
      // Only suffix values matter, not keys: verify that expectation.
      ['node', [5 => 145, 4545 => 3], ['node:145', 'node:3']],
      ['node', [5 => TRUE], ['node:1']],
      ['node', [5 => NULL], ['node:']],
      ['node', ['a' => NULL], ['node:']],
      ['node', ['a' => TRUE], ['node:1']],
      // Test the $glue parameter.
      ['config:system.menu', ['menu_name'], ['config:system.menu.menu_name'], '.'],
    ];
  }

  /**
   * @covers ::buildTags
   *
   * @dataProvider buildTagsProvider
   */
  public function testBuildTags($prefix, array $suffixes, array $expected, $glue = ':') {
    $this->assertEquals($expected, Cache::buildTags($prefix, $suffixes, $glue));
  }

}
